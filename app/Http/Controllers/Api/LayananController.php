<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Layanan;
use App\Rules\ValidFileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LayananController extends Controller
{
    public function index(Request $request)
    {
        $query = Layanan::query();

        if ($request->has('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->has('tahun_apbd')) {
            $query->where('tahun_apbd', $request->tahun_apbd);
        }

        $layanans = $query->orderBy('tahun_apbd', 'desc')
            ->orderBy('tipe')
            ->paginate($request->get('per_page', 10));

        $layanans->getCollection()->transform(function ($item) {
            $item->file_url = $item->file_dokumen
                ? Storage::disk('public')->url($item->file_dokumen) : null;
            return $item;
        });

        return $this->success($layanans);
    }

    public function store(Request $request)
    {
        try {
            Log::info('Store request received', [
                'request_data' => $request->except('file_dokumen'),
                'has_file' => $request->hasFile('file_dokumen'),
                'file_info' => $request->file('file_dokumen') ? [
                    'name' => $request->file('file_dokumen')->getClientOriginalName(),
                    'mime' => $request->file('file_dokumen')->getMimeType(),
                    'size' => $request->file('file_dokumen')->getSize(),
                ] : null,
            ]);

            $validated = $request->validate([
                'tipe'         => 'required|in:'.implode(',', Layanan::TIPE_LIST),
                'judul'        => 'required|string|max:255',
                'deskripsi'    => 'nullable|string',
                'tahun_apbd'   => 'required|integer|min:2000|max:2100',
                'file_dokumen' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
            ]);

            Log::info('Validation passed', ['validated_keys' => array_keys($validated)]);

            $data = collect($validated)->only(['tipe', 'judul', 'deskripsi', 'tahun_apbd'])->toArray();

            if ($request->hasFile('file_dokumen')) {
                $file = $request->file('file_dokumen');
                $originalName = $file->getClientOriginalName();
                
                Log::info('File upload attempt', [
                    'original_name' => $originalName,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'path' => $file->getRealPath(),
                ]);

                // Sanitize filename: remove special chars, replace spaces with underscore
                $safeFileName = $this->sanitizeFileName($originalName);
                Log::info('File name sanitized', ['original' => $originalName, 'safe' => $safeFileName]);

                // Store with safe filename
                $path = $file->storeAs('layanan', $safeFileName, 'public');
                
                if (!$path) {
                    Log::error('File storage failed', [
                        'filename' => $originalName,
                        'safe_filename' => $safeFileName,
                        'size' => $file->getSize(),
                    ]);
                    return $this->error('The file dokumen failed to upload.', 422, [
                        'file_dokumen' => ['The file dokumen failed to upload.']
                    ]);
                }
                
                $data['file_dokumen'] = $path;
                Log::info('File stored successfully', ['path' => $path, 'original_name' => $originalName]);
            }

            $layanan = Layanan::create($data);

            return $this->success($layanan, 'Layanan berhasil ditambahkan.', 201);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            Log::error('Validation failed in store', [
                'errors' => $ve->errors(),
                'request_data' => $request->except('file_dokumen'),
            ]);
            throw $ve;
        } catch (\Throwable $e) {
            Log::error('Layanan store error', [
                'message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->error('An error occurred while processing your request.', 500);
        }
    }

    private function sanitizeFileName($fileName)
    {
        // Get file extension
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $nameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);

        // Remove special characters, keep only alphanumeric, spaces, and hyphens
        $sanitized = preg_replace('/[^a-zA-Z0-9\s\-_]/u', '', $nameWithoutExt);

        // Replace multiple spaces/underscores with single underscore
        $sanitized = preg_replace('/[\s_]+/', '_', $sanitized);

        // Remove leading/trailing underscores
        $sanitized = trim($sanitized, '_');

        // Limit length (filesystem limit is usually 255, but keep margin for timestamp)
        $maxLength = 200;
        if (strlen($sanitized) > $maxLength) {
            $sanitized = substr($sanitized, 0, $maxLength);
        }

        // If name is empty after sanitization, use timestamp
        if (empty($sanitized)) {
            $sanitized = 'file_' . time();
        }

        return $sanitized . '.' . $extension;
    }

    public function show(Layanan $layanan)
    {
        $layanan->file_url = $layanan->file_dokumen
            ? Storage::disk('public')->url($layanan->file_dokumen) : null;

        return $this->success($layanan);
    }

    public function update(Request $request, Layanan $layanan)
    {
        try {
            Log::info('Update request received', [
                'layanan_id' => $layanan->id,
                'request_data' => $request->except('file_dokumen'),
                'has_file' => $request->hasFile('file_dokumen'),
            ]);

            $validated = $request->validate([
                'tipe'         => 'sometimes|in:'.implode(',', Layanan::TIPE_LIST),
                'judul'        => 'sometimes|required|string|max:255',
                'deskripsi'    => 'nullable|string',
                'tahun_apbd'   => 'sometimes|integer|min:2000|max:2100',
                'file_dokumen' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
            ]);

            Log::info('Validation passed for update', ['validated_keys' => array_keys($validated)]);

            $data = collect($validated)->only(['tipe', 'judul', 'deskripsi', 'tahun_apbd'])->toArray();

            if ($request->hasFile('file_dokumen')) {
                if ($layanan->file_dokumen) {
                    Storage::disk('public')->delete($layanan->file_dokumen);
                }
                
                $file = $request->file('file_dokumen');
                $originalName = $file->getClientOriginalName();
                
                Log::info('File update attempt', [
                    'original_name' => $originalName,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);

                // Sanitize filename
                $safeFileName = $this->sanitizeFileName($originalName);
                Log::info('File name sanitized', ['original' => $originalName, 'safe' => $safeFileName]);

                $path = $file->storeAs('layanan', $safeFileName, 'public');
                
                if (!$path) {
                    Log::error('File storage failed during update', [
                        'filename' => $originalName,
                        'safe_filename' => $safeFileName,
                        'size' => $file->getSize(),
                    ]);
                    return $this->error('The file dokumen failed to upload.', 422, [
                        'file_dokumen' => ['The file dokumen failed to upload.']
                    ]);
                }
                
                $data['file_dokumen'] = $path;
                Log::info('File updated successfully', ['path' => $path, 'original_name' => $originalName]);
            }

            $layanan->update($data);

            return $this->success($layanan, 'Layanan berhasil diperbarui.');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            Log::error('Validation failed in update', [
                'errors' => $ve->errors(),
                'layanan_id' => $layanan->id,
            ]);
            throw $ve;
        } catch (\Throwable $e) {
            Log::error('Layanan update error', [
                'message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->error('An error occurred while processing your request.', 500);
        }
    }

    public function destroy(Layanan $layanan)
    {
        if ($layanan->file_dokumen) {
            Storage::disk('public')->delete($layanan->file_dokumen);
        }
        $layanan->delete();

        return $this->success(null, 'Layanan berhasil dihapus.');
    }
}

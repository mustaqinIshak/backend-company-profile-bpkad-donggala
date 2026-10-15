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
            $validated = $request->validate([
                'tipe'         => 'required|in:'.implode(',', Layanan::TIPE_LIST),
                'judul'        => 'required|string|max:255',
                'deskripsi'    => 'nullable|string',
                'tahun_apbd'   => 'required|integer|min:2000|max:2100',
                'file_dokumen' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
            ]);

            $data = collect($validated)->only(['tipe', 'judul', 'deskripsi', 'tahun_apbd'])->toArray();

            if ($request->hasFile('file_dokumen')) {
                $file = $request->file('file_dokumen');
                
                Log::info('File upload attempt', [
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'path' => $file->getRealPath(),
                ]);

                $path = $file->store('layanan', 'public');
                
                if (!$path) {
                    Log::error('File storage failed', [
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                    ]);
                    return $this->error('The file dokumen failed to upload.', 422, [
                        'file_dokumen' => ['The file dokumen failed to upload.']
                    ]);
                }
                
                $data['file_dokumen'] = $path;
                Log::info('File stored successfully', ['path' => $path]);
            }

            $layanan = Layanan::create($data);

            return $this->success($layanan, 'Layanan berhasil ditambahkan.', 201);
        } catch (\Throwable $e) {
            Log::error('Layanan store error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->error('An error occurred while processing your request.', 500);
        }
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
            $validated = $request->validate([
                'tipe'         => 'sometimes|in:'.implode(',', Layanan::TIPE_LIST),
                'judul'        => 'sometimes|required|string|max:255',
                'deskripsi'    => 'nullable|string',
                'tahun_apbd'   => 'sometimes|integer|min:2000|max:2100',
                'file_dokumen' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
            ]);

            $data = collect($validated)->only(['tipe', 'judul', 'deskripsi', 'tahun_apbd'])->toArray();

            if ($request->hasFile('file_dokumen')) {
                if ($layanan->file_dokumen) {
                    Storage::disk('public')->delete($layanan->file_dokumen);
                }
                
                $file = $request->file('file_dokumen');
                
                Log::info('File update attempt', [
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);

                $path = $file->store('layanan', 'public');
                
                if (!$path) {
                    Log::error('File storage failed during update', [
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                    ]);
                    return $this->error('The file dokumen failed to upload.', 422, [
                        'file_dokumen' => ['The file dokumen failed to upload.']
                    ]);
                }
                
                $data['file_dokumen'] = $path;
                Log::info('File updated successfully', ['path' => $path]);
            }

            $layanan->update($data);

            return $this->success($layanan, 'Layanan berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Layanan update error', [
                'message' => $e->getMessage(),
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

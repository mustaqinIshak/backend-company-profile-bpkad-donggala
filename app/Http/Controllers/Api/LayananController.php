<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Layanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $request->validate([
            'tipe'         => 'required|in:'.implode(',', Layanan::TIPE_LIST),
            'judul'        => 'required|string|max:255',
            'deskripsi'    => 'nullable|string',
            'tahun_apbd'   => 'required|integer|min:2000|max:2100',
            'file_dokumen' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
        ]);

        $data = $request->only(['tipe', 'judul', 'deskripsi', 'tahun_apbd']);

        if ($request->hasFile('file_dokumen')) {
            $data['file_dokumen'] = $request->file('file_dokumen')->store('layanan', 'public');
        }

        $layanan = Layanan::create($data);

        return $this->success($layanan, 'Layanan berhasil ditambahkan.', 201);
    }

    public function show(Layanan $layanan)
    {
        $layanan->file_url = $layanan->file_dokumen
            ? Storage::disk('public')->url($layanan->file_dokumen) : null;

        return $this->success($layanan);
    }

    public function update(Request $request, Layanan $layanan)
    {
        $request->validate([
            'tipe'         => 'sometimes|in:'.implode(',', Layanan::TIPE_LIST),
            'judul'        => 'sometimes|required|string|max:255',
            'deskripsi'    => 'nullable|string',
            'tahun_apbd'   => 'sometimes|integer|min:2000|max:2100',
            'file_dokumen' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
        ]);

        $data = $request->only(['tipe', 'judul', 'deskripsi', 'tahun_apbd']);

        if ($request->hasFile('file_dokumen')) {
            if ($layanan->file_dokumen) {
                Storage::disk('public')->delete($layanan->file_dokumen);
            }
            $data['file_dokumen'] = $request->file('file_dokumen')->store('layanan', 'public');
        }

        $layanan->update($data);

        return $this->success($layanan, 'Layanan berhasil diperbarui.');
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

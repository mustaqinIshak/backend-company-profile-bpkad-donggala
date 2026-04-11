<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BeritaController extends Controller
{
    public function index(Request $request)
    {
        $query = Berita::query();

        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->boolean('published_only', false)) {
            $query->where('is_published', true);
        }

        $beritas = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        $beritas->getCollection()->transform(function ($item) {
            $item->gambar_url = $item->gambar
                ? Storage::disk('public')->url($item->gambar) : null;
            return $item;
        });

        return $this->success($beritas);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul'        => 'required|string|max:255',
            'isi'          => 'required|string',
            'gambar'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'kategori'     => 'nullable|string|max:100',
            'is_published' => 'nullable|boolean',
        ]);

        $data = $request->only(['judul', 'isi', 'kategori']);
        $data['is_published'] = $request->boolean('is_published', false);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('berita', 'public');
        }

        $berita = Berita::create($data);

        return $this->success($berita, 'Berita berhasil ditambahkan.', 201);
    }

    public function show(Berita $berita)
    {
        $berita->gambar_url = $berita->gambar
            ? Storage::disk('public')->url($berita->gambar) : null;

        return $this->success($berita);
    }

    public function showBySlug(string $slug)
    {
        $berita = Berita::where('slug', $slug)->firstOrFail();
        $berita->gambar_url = $berita->gambar
            ? Storage::disk('public')->url($berita->gambar) : null;

        return $this->success($berita);
    }

    public function update(Request $request, Berita $berita)
    {
        $request->validate([
            'judul'        => 'sometimes|required|string|max:255',
            'isi'          => 'sometimes|required|string',
            'gambar'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'kategori'     => 'nullable|string|max:100',
            'is_published' => 'nullable|boolean',
        ]);

        $data = $request->only(['judul', 'isi', 'kategori']);

        if ($request->has('is_published')) {
            $data['is_published'] = $request->boolean('is_published');
        }

        if ($request->hasFile('gambar')) {
            if ($berita->gambar) {
                Storage::disk('public')->delete($berita->gambar);
            }
            $data['gambar'] = $request->file('gambar')->store('berita', 'public');
        }

        $berita->update($data);

        return $this->success($berita, 'Berita berhasil diperbarui.');
    }

    public function destroy(Berita $berita)
    {
        if ($berita->gambar) {
            Storage::disk('public')->delete($berita->gambar);
        }
        $berita->delete();

        return $this->success(null, 'Berita berhasil dihapus.');
    }
}

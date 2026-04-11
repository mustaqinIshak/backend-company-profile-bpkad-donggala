<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jumbotron;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JumbotronController extends Controller
{
    public function index()
    {
        $jumbotrons = Jumbotron::orderBy('urutan')->get()->map(function ($item) {
            $item->gambar_url = $item->gambar
                ? Storage::disk('public')->url($item->gambar) : null;
            return $item;
        });

        return $this->success($jumbotrons);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul'     => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
            'gambar'    => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'urutan'    => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $path = $request->file('gambar')->store('jumbotron', 'public');

        $jumbotron = Jumbotron::create([
            'judul'     => $request->judul,
            'deskripsi' => $request->deskripsi,
            'gambar'    => $path,
            'urutan'    => $request->urutan ?? Jumbotron::max('urutan') + 1,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->success($jumbotron, 'Jumbotron berhasil ditambahkan.', 201);
    }

    public function show(Jumbotron $jumbotron)
    {
        $jumbotron->gambar_url = $jumbotron->gambar
            ? Storage::disk('public')->url($jumbotron->gambar) : null;

        return $this->success($jumbotron);
    }

    public function update(Request $request, Jumbotron $jumbotron)
    {
        $request->validate([
            'judul'     => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
            'gambar'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'urutan'    => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->only(['judul', 'deskripsi', 'urutan']);

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        if ($request->hasFile('gambar')) {
            if ($jumbotron->gambar) {
                Storage::disk('public')->delete($jumbotron->gambar);
            }
            $data['gambar'] = $request->file('gambar')->store('jumbotron', 'public');
        }

        $jumbotron->update($data);

        return $this->success($jumbotron, 'Jumbotron berhasil diperbarui.');
    }

    public function destroy(Jumbotron $jumbotron)
    {
        if ($jumbotron->gambar) {
            Storage::disk('public')->delete($jumbotron->gambar);
        }
        $jumbotron->delete();

        return $this->success(null, 'Jumbotron berhasil dihapus.');
    }

    public function toggleActive(Jumbotron $jumbotron)
    {
        $jumbotron->update(['is_active' => !$jumbotron->is_active]);

        return $this->success($jumbotron, 'Status jumbotron berhasil diubah.');
    }
}

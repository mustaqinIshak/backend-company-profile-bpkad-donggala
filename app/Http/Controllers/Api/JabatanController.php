<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\Organisasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JabatanController extends Controller
{
    public function index(Organisasi $organisasi)
    {
        return $this->success($organisasi->jabatans);
    }

    public function store(Request $request, Organisasi $organisasi)
    {
        $request->validate([
            'nama_jabatan' => 'required|string|max:255',
            'nama_pejabat' => 'required|string|max:255',
            'nip'          => 'nullable|string|max:30',
            'foto'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'tugas_fungsi' => 'nullable|array',
            'tugas_fungsi.*' => 'string',
        ]);

        $data = $request->only(['nama_jabatan', 'nama_pejabat', 'nip', 'tugas_fungsi']);
        $data['organisasi_id'] = $organisasi->id;

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('profile', 'public');
        }

        $jabatan = Jabatan::create($data);

        return $this->success($jabatan, 'Jabatan berhasil ditambahkan.', 201);
    }

    public function show(Organisasi $organisasi, Jabatan $jabatan)
    {
        $this->ensureBelongsTo($organisasi, $jabatan);

        $jabatan->foto_url = $jabatan->foto
            ? Storage::disk('public')->url($jabatan->foto) : null;

        return $this->success($jabatan);
    }

    public function update(Request $request, Organisasi $organisasi, Jabatan $jabatan)
    {
        $this->ensureBelongsTo($organisasi, $jabatan);

        $request->validate([
            'nama_jabatan'   => 'sometimes|required|string|max:255',
            'nama_pejabat'   => 'sometimes|required|string|max:255',
            'nip'            => 'nullable|string|max:30',
            'foto'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'tugas_fungsi'   => 'nullable|array',
            'tugas_fungsi.*' => 'string',
        ]);

        $data = $request->only(['nama_jabatan', 'nama_pejabat', 'nip', 'tugas_fungsi']);

        if ($request->hasFile('foto')) {
            if ($jabatan->foto) {
                Storage::disk('public')->delete($jabatan->foto);
            }
            $data['foto'] = $request->file('foto')->store('profile', 'public');
        }

        $jabatan->update($data);

        return $this->success($jabatan, 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Organisasi $organisasi, Jabatan $jabatan)
    {
        $this->ensureBelongsTo($organisasi, $jabatan);

        if ($jabatan->foto) {
            Storage::disk('public')->delete($jabatan->foto);
        }

        $jabatan->delete();

        return $this->success(null, 'Jabatan berhasil dihapus.');
    }

    private function ensureBelongsTo(Organisasi $organisasi, Jabatan $jabatan): void
    {
        abort_if($jabatan->organisasi_id !== $organisasi->id, 404, 'Jabatan tidak ditemukan pada bidang ini.');
    }
}

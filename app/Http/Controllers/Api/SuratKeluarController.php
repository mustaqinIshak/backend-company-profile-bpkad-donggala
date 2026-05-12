<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class SuratKeluarController extends Controller
{
    /**
     * GET /admin/surat-keluar
     */
    public function index(Request $request)
    {
        $query = SuratKeluar::with(['dibuatOleh:id,name', 'disetujuiOleh:id,name'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tahun')) {
            $query->whereYear('created_at', $request->tahun);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('perihal', 'like', "%{$search}%")
                  ->orWhere('tujuan', 'like', "%{$search}%")
                  ->orWhere('no_agenda', 'like', "%{$search}%")
                  ->orWhere('nomor_surat', 'like', "%{$search}%");
            });
        }

        $surat = $query->paginate($request->integer('per_page', 15));

        return $this->success($surat);
    }

    /**
     * POST /admin/surat-keluar
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'perihal'         => 'required|string|max:500',
            'tujuan'          => 'required|string|max:255',
            'instansi_tujuan' => 'required|string|max:255',
            'tanggal_surat'   => 'nullable|date',
            'file_surat'      => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'catatan'         => 'nullable|string',
        ]);

        if ($request->hasFile('file_surat')) {
            $data['file_surat'] = $request->file('file_surat')->store('surat/keluar', 'public');
        }

        $data['dibuat_oleh'] = $request->user()->id;

        $surat = SuratKeluar::create($data);

        return $this->success($surat->load('dibuatOleh:id,name'), 'Surat keluar berhasil dibuat.', 201);
    }

    /**
     * GET /admin/surat-keluar/{suratKeluar}
     */
    public function show(SuratKeluar $suratKeluar)
    {
        return $this->success(
            $suratKeluar->load(['dibuatOleh:id,name', 'disetujuiOleh:id,name'])
        );
    }

    /**
     * POST /admin/surat-keluar/{suratKeluar}
     */
    public function update(Request $request, SuratKeluar $suratKeluar)
    {
        if (in_array($suratKeluar->status, ['disetujui', 'dikirim', 'arsip'])) {
            return $this->error('Surat yang sudah disetujui atau dikirim tidak dapat diubah.', 422);
        }

        $data = $request->validate([
            'perihal'         => 'sometimes|required|string|max:500',
            'tujuan'          => 'sometimes|required|string|max:255',
            'instansi_tujuan' => 'sometimes|required|string|max:255',
            'tanggal_surat'   => 'nullable|date',
            'file_surat'      => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'catatan'         => 'nullable|string',
        ]);

        if ($request->hasFile('file_surat')) {
            if ($suratKeluar->file_surat) {
                Storage::disk('public')->delete($suratKeluar->file_surat);
            }
            $data['file_surat'] = $request->file('file_surat')->store('surat/keluar', 'public');
        }

        $suratKeluar->update($data);

        return $this->success($suratKeluar->load(['dibuatOleh:id,name', 'disetujuiOleh:id,name']), 'Surat keluar diperbarui.');
    }

    /**
     * PATCH /admin/surat-keluar/{suratKeluar}/ajukan
     * Ajukan surat ke pimpinan untuk persetujuan (oleh petugas_surat)
     */
    public function ajukan(SuratKeluar $suratKeluar)
    {
        if ($suratKeluar->status !== 'draft') {
            return $this->error('Hanya surat berstatus draft yang dapat diajukan.', 422);
        }

        $suratKeluar->update(['status' => 'menunggu_persetujuan']);

        return $this->success($suratKeluar, 'Surat keluar diajukan untuk persetujuan.');
    }

    /**
     * PATCH /admin/surat-keluar/{suratKeluar}/setujui
     * Setujui surat (oleh pimpinan)
     */
    public function setujui(Request $request, SuratKeluar $suratKeluar)
    {
        if ($suratKeluar->status !== 'menunggu_persetujuan') {
            return $this->error('Hanya surat yang menunggu persetujuan yang dapat disetujui.', 422);
        }

        $data = $request->validate([
            'nomor_surat'   => 'required|string|max:100',
            'tanggal_surat' => 'required|date',
            'catatan'       => 'nullable|string',
        ]);

        $suratKeluar->update(array_merge($data, [
            'status'        => 'disetujui',
            'disetujui_oleh' => $request->user()->id,
        ]));

        return $this->success($suratKeluar->load(['dibuatOleh:id,name', 'disetujuiOleh:id,name']), 'Surat keluar disetujui.');
    }

    /**
     * PATCH /admin/surat-keluar/{suratKeluar}/kirim
     * Tandai surat sudah dikirim (oleh petugas_surat / admin / super_admin)
     */
    public function kirim(SuratKeluar $suratKeluar)
    {
        if ($suratKeluar->status !== 'disetujui') {
            return $this->error('Hanya surat yang sudah disetujui yang dapat dikirim.', 422);
        }

        $suratKeluar->update([
            'status'        => 'dikirim',
            'tanggal_kirim' => Carbon::today()->toDateString(),
        ]);

        return $this->success($suratKeluar, 'Surat keluar ditandai sudah dikirim.');
    }

    /**
     * PATCH /admin/surat-keluar/{suratKeluar}/arsip
     */
    public function arsip(SuratKeluar $suratKeluar)
    {
        $suratKeluar->update(['status' => 'arsip']);

        return $this->success($suratKeluar, 'Surat keluar diarsipkan.');
    }

    /**
     * DELETE /admin/surat-keluar/{suratKeluar}
     */
    public function destroy(SuratKeluar $suratKeluar)
    {
        if (in_array($suratKeluar->status, ['dikirim', 'arsip'])) {
            return $this->error('Surat yang sudah dikirim atau diarsipkan tidak dapat dihapus.', 422);
        }

        if ($suratKeluar->file_surat) {
            Storage::disk('public')->delete($suratKeluar->file_surat);
        }

        $suratKeluar->delete();

        return $this->success(null, 'Surat keluar berhasil dihapus.');
    }
}

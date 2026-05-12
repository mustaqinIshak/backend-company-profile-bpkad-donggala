<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Disposisi;
use App\Models\SuratMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DisposisiController extends Controller
{
    /**
     * GET /admin/surat-masuk/{suratMasuk}/disposisi
     */
    public function index(SuratMasuk $suratMasuk)
    {
        $disposisis = $suratMasuk->disposisis()
            ->with(['dariAdmin:id,name', 'kepadaAdmin:id,name'])
            ->orderByDesc('tanggal_disposisi')
            ->get();

        return $this->success($disposisis);
    }

    /**
     * POST /admin/surat-masuk/{suratMasuk}/disposisi
     */
    public function store(Request $request, SuratMasuk $suratMasuk)
    {
        $data = $request->validate([
            'kepada_admin_id'    => 'required|exists:admins,id',
            'instruksi'          => 'required|string',
            'tanggal_disposisi'  => 'nullable|date',
        ]);

        $data['surat_masuk_id']   = $suratMasuk->id;
        $data['dari_admin_id']    = $request->user()->id;
        $data['tanggal_disposisi'] = $data['tanggal_disposisi'] ?? Carbon::today()->toDateString();

        $disposisi = Disposisi::create($data);

        // Update surat masuk status to diproses if still 'baru'
        if ($suratMasuk->status === 'baru') {
            $suratMasuk->update(['status' => 'diproses']);
        }

        return $this->success(
            $disposisi->load(['dariAdmin:id,name', 'kepadaAdmin:id,name']),
            'Disposisi berhasil dibuat.',
            201
        );
    }

    /**
     * GET /admin/surat-masuk/{suratMasuk}/disposisi/{disposisi}
     */
    public function show(SuratMasuk $suratMasuk, Disposisi $disposisi)
    {
        $this->ensureBelongsToSurat($suratMasuk, $disposisi);

        return $this->success(
            $disposisi->load(['suratMasuk', 'dariAdmin:id,name', 'kepadaAdmin:id,name'])
        );
    }

    /**
     * PATCH /admin/surat-masuk/{suratMasuk}/disposisi/{disposisi}/status
     */
    public function updateStatus(Request $request, SuratMasuk $suratMasuk, Disposisi $disposisi)
    {
        $this->ensureBelongsToSurat($suratMasuk, $disposisi);

        $data = $request->validate([
            'status' => 'required|in:belum_diproses,sedang_diproses,selesai',
        ]);

        if ($data['status'] === 'selesai') {
            $data['tanggal_selesai'] = Carbon::today()->toDateString();
        }

        $disposisi->update($data);

        // If all disposisi are selesai, mark surat masuk as selesai
        $allDone = $suratMasuk->disposisis()->where('status', '!=', 'selesai')->doesntExist();
        if ($allDone && $suratMasuk->disposisis()->exists()) {
            $suratMasuk->update(['status' => 'selesai']);
        }

        return $this->success($disposisi, 'Status disposisi diperbarui.');
    }

    /**
     * PATCH /admin/surat-masuk/{suratMasuk}/disposisi/{disposisi}/balas
     */
    public function balas(Request $request, SuratMasuk $suratMasuk, Disposisi $disposisi)
    {
        $this->ensureBelongsToSurat($suratMasuk, $disposisi);

        // Only the recipient can reply
        if ($request->user()->id !== $disposisi->kepada_admin_id) {
            return $this->error('Hanya penerima disposisi yang dapat membalas.', 403);
        }

        $data = $request->validate([
            'catatan_balasan' => 'required|string',
        ]);

        $disposisi->update(array_merge($data, [
            'status'          => 'sedang_diproses',
        ]));

        return $this->success($disposisi, 'Balasan disposisi berhasil disimpan.');
    }

    /**
     * DELETE /admin/surat-masuk/{suratMasuk}/disposisi/{disposisi}
     */
    public function destroy(SuratMasuk $suratMasuk, Disposisi $disposisi)
    {
        $this->ensureBelongsToSurat($suratMasuk, $disposisi);

        $disposisi->delete();

        return $this->success(null, 'Disposisi berhasil dihapus.');
    }

    private function ensureBelongsToSurat(SuratMasuk $suratMasuk, Disposisi $disposisi): void
    {
        if ($disposisi->surat_masuk_id !== $suratMasuk->id) {
            abort(404, 'Disposisi tidak ditemukan pada surat masuk ini.');
        }
    }
}

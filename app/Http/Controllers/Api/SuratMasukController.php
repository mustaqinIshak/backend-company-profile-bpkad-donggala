<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuratMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class SuratMasukController extends Controller
{
    /**
     * GET /admin/surat-masuk
     */
    public function index(Request $request)
    {
        $query = SuratMasuk::with('diterimaOleh:id,name')
            ->orderByDesc('tanggal_terima');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal_terima', $request->tahun);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('perihal', 'like', "%{$search}%")
                  ->orWhere('pengirim', 'like', "%{$search}%")
                  ->orWhere('no_agenda', 'like', "%{$search}%")
                  ->orWhere('nomor_surat', 'like', "%{$search}%");
            });
        }

        $surat = $query->paginate($request->integer('per_page', 15));

        return $this->success($surat);
    }

    /**
     * POST /admin/surat-masuk
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nomor_surat'       => 'nullable|string|max:100',
            'pengirim'          => 'required|string|max:255',
            'instansi_pengirim' => 'required|string|max:255',
            'perihal'           => 'required|string|max:500',
            'tanggal_surat'     => 'required|date',
            'tanggal_terima'    => 'required|date',
            'file_surat'        => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'catatan'           => 'nullable|string',
        ]);

        if ($request->hasFile('file_surat')) {
            $data['file_surat'] = $request->file('file_surat')->store('surat/masuk', 'public');
        }

        $data['diterima_oleh'] = $request->user()->id;

        $surat = SuratMasuk::create($data);

        return $this->success($surat->load('diterimaOleh:id,name'), 'Surat masuk berhasil dicatat.', 201);
    }

    /**
     * GET /admin/surat-masuk/{suratMasuk}
     */
    public function show(SuratMasuk $suratMasuk)
    {
        return $this->success(
            $suratMasuk->load(['diterimaOleh:id,name', 'disposisis.dariAdmin:id,name', 'disposisis.kepadaAdmin:id,name'])
        );
    }

    /**
     * POST /admin/surat-masuk/{suratMasuk}
     */
    public function update(Request $request, SuratMasuk $suratMasuk)
    {
        $data = $request->validate([
            'nomor_surat'       => 'nullable|string|max:100',
            'pengirim'          => 'sometimes|required|string|max:255',
            'instansi_pengirim' => 'sometimes|required|string|max:255',
            'perihal'           => 'sometimes|required|string|max:500',
            'tanggal_surat'     => 'sometimes|required|date',
            'tanggal_terima'    => 'sometimes|required|date',
            'file_surat'        => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'status'            => 'sometimes|required|in:baru,diproses,selesai,arsip',
            'catatan'           => 'nullable|string',
        ]);

        if ($request->hasFile('file_surat')) {
            if ($suratMasuk->file_surat) {
                Storage::disk('public')->delete($suratMasuk->file_surat);
            }
            $data['file_surat'] = $request->file('file_surat')->store('surat/masuk', 'public');
        }

        $suratMasuk->update($data);

        return $this->success($suratMasuk->load('diterimaOleh:id,name'), 'Surat masuk diperbarui.');
    }

    /**
     * PATCH /admin/surat-masuk/{suratMasuk}/status
     */
    public function updateStatus(Request $request, SuratMasuk $suratMasuk)
    {
        $data = $request->validate([
            'status'  => 'required|in:baru,diproses,selesai,arsip',
            'catatan' => 'nullable|string',
        ]);

        $suratMasuk->update($data);

        return $this->success($suratMasuk, 'Status surat masuk diperbarui.');
    }

    /**
     * DELETE /admin/surat-masuk/{suratMasuk}
     */
    public function destroy(SuratMasuk $suratMasuk)
    {
        if ($suratMasuk->file_surat) {
            Storage::disk('public')->delete($suratMasuk->file_surat);
        }

        $suratMasuk->delete();

        return $this->success(null, 'Surat masuk berhasil dihapus.');
    }
}

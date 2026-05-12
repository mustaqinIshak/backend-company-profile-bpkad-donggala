<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tamu;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class TamuController extends Controller
{
    /**
     * POST /tamu (public)
     * Registrasi tamu baru di loby.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'nama'               => 'required|string|max:255',
            'instansi_asal'      => 'nullable|string|max:255',
            'no_identitas'       => 'nullable|string|max:50',
            'jenis_identitas'    => 'nullable|in:ktp,sim,paspor,lainnya',
            'keperluan'          => 'required|string',
            'nama_yang_dituju'   => 'required|string|max:255',
            'jabatan_yang_dituju'=> 'nullable|string|max:255',
            'foto'               => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('tamu', 'public');
        }

        $data['tanggal_kunjungan'] = Carbon::today()->toDateString();
        $data['waktu_masuk']       = Carbon::now();

        $tamu = Tamu::create($data);

        return $this->success($tamu, 'Registrasi tamu berhasil.', 201);
    }

    /**
     * GET /admin/tamu
     * Daftar tamu dengan filter opsional.
     */
    public function index(Request $request)
    {
        $query = Tamu::query()->orderByDesc('waktu_masuk');

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_kunjungan', $request->tanggal);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nama_yang_dituju', 'like', "%{$search}%")
                  ->orWhere('nomor_antrian', 'like', "%{$search}%");
            });
        }

        $tamus = $query->paginate($request->integer('per_page', 15));

        return $this->success($tamus);
    }

    /**
     * GET /admin/tamu/{tamu}
     */
    public function show(Tamu $tamu)
    {
        return $this->success($tamu);
    }

    /**
     * PATCH /admin/tamu/{tamu}/status
     */
    public function updateStatus(Request $request, Tamu $tamu)
    {
        $data = $request->validate([
            'status'  => 'required|in:diterima,ditolak,selesai',
            'catatan' => 'nullable|string',
        ]);

        $tamu->update($data);

        return $this->success($tamu, 'Status tamu diperbarui.');
    }

    /**
     * PATCH /admin/tamu/{tamu}/checkout
     */
    public function checkout(Tamu $tamu)
    {
        if ($tamu->waktu_keluar) {
            return $this->error('Tamu sudah melakukan checkout.', 422);
        }

        $tamu->update([
            'waktu_keluar' => Carbon::now(),
            'status'       => 'selesai',
        ]);

        return $this->success($tamu, 'Checkout tamu berhasil.');
    }

    /**
     * DELETE /admin/tamu/{tamu}
     */
    public function destroy(Tamu $tamu)
    {
        if ($tamu->foto) {
            Storage::disk('public')->delete($tamu->foto);
        }

        $tamu->delete();

        return $this->success(null, 'Data tamu berhasil dihapus.');
    }
}

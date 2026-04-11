<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organisasi;
use Illuminate\Http\Request;

class OrganisasiController extends Controller
{
    public function index()
    {
        $organisasis = Organisasi::with('jabatans')->get();

        return $this->success($organisasis);
    }

    public function show(Organisasi $organisasi)
    {
        return $this->success($organisasi->load('jabatans'));
    }

    public function showByBidang(string $bidang)
    {
        if (!in_array($bidang, Organisasi::BIDANG_LIST)) {
            return $this->error('Bidang tidak valid. Pilihan: '.implode(', ', Organisasi::BIDANG_LIST), 422);
        }

        $organisasi = Organisasi::with('jabatans')->where('bidang', $bidang)->first();

        if (!$organisasi) {
            return $this->success(null, "Data bidang {$bidang} belum diisi.");
        }

        return $this->success($organisasi);
    }

    public function storeOrUpdate(Request $request, string $bidang)
    {
        if (!in_array($bidang, Organisasi::BIDANG_LIST)) {
            return $this->error('Bidang tidak valid. Pilihan: '.implode(', ', Organisasi::BIDANG_LIST), 422);
        }

        $request->validate([
            'deskripsi' => 'nullable|string',
        ]);

        $organisasi = Organisasi::updateOrCreate(
            ['bidang' => $bidang],
            ['deskripsi' => $request->deskripsi]
        );

        return $this->success($organisasi->load('jabatans'), 'Data organisasi berhasil disimpan.');
    }
}

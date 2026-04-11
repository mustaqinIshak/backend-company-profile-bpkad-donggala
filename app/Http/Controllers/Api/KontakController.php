<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kontak;
use Illuminate\Http\Request;

class KontakController extends Controller
{
    // Public: submit contact form
    public function store(Request $request)
    {
        $request->validate([
            'nama'       => 'required|string|max:100',
            'email'      => 'required|email|max:100',
            'no_telepon' => 'nullable|string|max:20',
            'subjek'     => 'required|string|max:255',
            'pesan'      => 'required|string|max:2000',
        ]);

        $kontak = Kontak::create([
            'nama'       => $request->nama,
            'email'      => $request->email,
            'no_telepon' => $request->no_telepon,
            'subjek'     => $request->subjek,
            'pesan'      => $request->pesan,
            'status'     => Kontak::STATUS_BELUM_DIBACA,
        ]);

        return $this->success($kontak, 'Pesan berhasil dikirim. Terima kasih.', 201);
    }

    // Admin: list all messages
    public function index(Request $request)
    {
        $query = Kontak::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $kontaks = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return $this->success($kontaks);
    }

    // Admin: get single message
    public function show(Kontak $kontak)
    {
        // Auto-mark as read when viewed
        if ($kontak->status === Kontak::STATUS_BELUM_DIBACA) {
            $kontak->update(['status' => Kontak::STATUS_SUDAH_DIBACA]);
        }

        return $this->success($kontak);
    }

    // Admin: update status
    public function updateStatus(Request $request, Kontak $kontak)
    {
        $request->validate([
            'status' => 'required|in:belum_dibaca,sudah_dibaca,dibalas',
        ]);

        $kontak->update(['status' => $request->status]);

        return $this->success($kontak, 'Status pesan berhasil diperbarui.');
    }

    // Admin: delete message
    public function destroy(Kontak $kontak)
    {
        $kontak->delete();

        return $this->success(null, 'Pesan berhasil dihapus.');
    }
}

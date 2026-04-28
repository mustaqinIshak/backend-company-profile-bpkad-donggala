<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        $profile = Profile::first();
        if (!$profile) {
            return $this->success(null, 'Profil belum diisi.');
        }

        $profile->logo_url              = $profile->logo
            ? Storage::disk('public')->url($profile->logo) : null;
        $profile->struktur_url          = $profile->struktur_organisasi
            ? Storage::disk('public')->url($profile->struktur_organisasi) : null;

        return $this->success($profile);
    }

    public function update(Request $request)
    {
        $request->validate([
            'nama_instansi'       => 'sometimes|required|string|max:255',
            'visi'                => 'sometimes|nullable|string',
            'misi'                => 'sometimes|nullable|string',
            'sejarah'             => 'sometimes|nullable|string',
            'alamat'              => 'sometimes|nullable|string|max:500',
            'telepon'             => 'sometimes|nullable|string|max:20',
            'email'               => 'sometimes|nullable|email|max:100',
            'website'             => 'sometimes|nullable|url|max:200',
            'logo'                => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'struktur_organisasi' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $profile = Profile::firstOrNew([]);
        $data    = $request->only([
            'nama_instansi', 'visi', 'misi', 'sejarah',
            'alamat', 'telepon', 'email', 'website',
        ]);

        if ($request->hasFile('logo')) {
            if ($profile->logo) {
                Storage::disk('public')->delete($profile->logo);
            }
            $data['logo'] = $request->file('logo')->store('profile', 'public');
        }

        if ($request->hasFile('struktur_organisasi')) {
            if ($profile->struktur_organisasi) {
                Storage::disk('public')->delete($profile->struktur_organisasi);
            }
            $data['struktur_organisasi'] = $request->file('struktur_organisasi')
                ->store('profile', 'public');
        }

        $profile->fill($data)->save();

        $profile->logo_url     = $profile->logo
            ? Storage::disk('public')->url($profile->logo) : null;
        $profile->struktur_url = $profile->struktur_organisasi
            ? Storage::disk('public')->url($profile->struktur_organisasi) : null;

        return $this->success($profile, 'Profil berhasil diperbarui.');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Profile extends Model
{
    protected $fillable = [
        'nama_instansi',
        'visi',
        'misi',
        'sejarah',
        'alamat',
        'telepon',
        'email',
        'website',
        'logo',
        'struktur_organisasi',
    ];

    protected $appends = ['logo_url', 'struktur_url'];

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->logo
                ? Storage::disk('public')->url($this->logo)
                : null,
        );
    }

    protected function strukturUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->struktur_organisasi
                ? Storage::disk('public')->url($this->struktur_organisasi)
                : null,
        );
    }
}

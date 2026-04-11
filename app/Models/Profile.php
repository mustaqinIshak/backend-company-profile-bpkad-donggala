<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kontak extends Model
{
    const STATUS_BELUM_DIBACA = 'belum_dibaca';
    const STATUS_SUDAH_DIBACA = 'sudah_dibaca';
    const STATUS_DIBALAS      = 'dibalas';

    protected $fillable = [
        'nama',
        'email',
        'no_telepon',
        'subjek',
        'pesan',
        'status',
    ];
}

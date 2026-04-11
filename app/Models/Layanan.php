<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Layanan extends Model
{
    const TIPE_LIST = [
        'penganggaran',
        'penatausahaan',
        'pelaporan_pertanggungjawaban',
        'perencanaan_evaluasi',
        'perjanjian_kinerja',
    ];

    protected $fillable = [
        'tipe',
        'judul',
        'deskripsi',
        'tahun_apbd',
        'file_dokumen',
    ];

    protected function casts(): array
    {
        return [
            'tahun_apbd' => 'integer',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organisasi extends Model
{
    const BIDANG_LIST = [
        'sekretariat',
        'aset',
        'perbendaharaan',
        'akuntansi',
        'anggaran',
    ];

    protected $fillable = [
        'bidang',
        'deskripsi',
    ];

    public function jabatans(): HasMany
    {
        return $this->hasMany(Jabatan::class);
    }
}

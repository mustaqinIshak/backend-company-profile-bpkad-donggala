<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disposisi extends Model
{
    protected $fillable = [
        'surat_masuk_id',
        'dari_admin_id',
        'kepada_admin_id',
        'instruksi',
        'catatan_balasan',
        'status',
        'tanggal_disposisi',
        'tanggal_selesai',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_disposisi' => 'date',
            'tanggal_selesai'   => 'date',
        ];
    }

    public function suratMasuk(): BelongsTo
    {
        return $this->belongsTo(SuratMasuk::class);
    }

    public function dariAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'dari_admin_id');
    }

    public function kepadaAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'kepada_admin_id');
    }
}

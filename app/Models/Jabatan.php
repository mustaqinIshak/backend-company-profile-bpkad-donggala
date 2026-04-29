<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Jabatan extends Model
{
    protected $fillable = [
        'organisasi_id',
        'nama_jabatan',
        'nama_pejabat',
        'nip',
        'foto',
        'tugas_fungsi',
    ];

    protected $appends = ['foto_url'];

    protected function casts(): array
    {
        return [
            'tugas_fungsi' => 'array',
        ];
    }

    protected function fotoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->foto
                ? Storage::disk('public')->url($this->foto)
                : null,
        );
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class);
    }
}

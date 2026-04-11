<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected function casts(): array
    {
        return [
            'tugas_fungsi' => 'array',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class);
    }
}

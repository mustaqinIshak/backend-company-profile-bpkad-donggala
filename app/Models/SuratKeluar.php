<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class SuratKeluar extends Model
{
    protected $fillable = [
        'no_agenda',
        'nomor_surat',
        'perihal',
        'tujuan',
        'instansi_tujuan',
        'tanggal_surat',
        'tanggal_kirim',
        'file_surat',
        'status',
        'catatan',
        'dibuat_oleh',
        'disetujui_oleh',
    ];

    protected $appends = ['file_url'];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'tanggal_kirim' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $surat) {
            if (empty($surat->no_agenda)) {
                $year  = Carbon::now()->year;
                $count = static::whereYear('created_at', $year)->count();
                $surat->no_agenda = 'SK/' . $year . '/' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_surat
            ? Storage::disk('public')->url($this->file_surat)
            : null;
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'dibuat_oleh');
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'disetujui_oleh');
    }
}

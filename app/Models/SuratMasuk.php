<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class SuratMasuk extends Model
{
    protected $fillable = [
        'no_agenda',
        'nomor_surat',
        'pengirim',
        'instansi_pengirim',
        'perihal',
        'tanggal_surat',
        'tanggal_terima',
        'file_surat',
        'status',
        'catatan',
        'diterima_oleh',
    ];

    protected $appends = ['file_url'];

    protected function casts(): array
    {
        return [
            'tanggal_surat'  => 'date',
            'tanggal_terima' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $surat) {
            if (empty($surat->no_agenda)) {
                $year  = Carbon::now()->year;
                $count = static::whereYear('created_at', $year)->count();
                $surat->no_agenda = 'SM/' . $year . '/' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_surat
            ? Storage::disk('public')->url($this->file_surat)
            : null;
    }

    public function diterimaOleh(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'diterima_oleh');
    }

    public function disposisis(): HasMany
    {
        return $this->hasMany(Disposisi::class);
    }
}

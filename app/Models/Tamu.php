<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Tamu extends Model
{
    protected $fillable = [
        'nama',
        'instansi_asal',
        'no_identitas',
        'jenis_identitas',
        'keperluan',
        'nama_yang_dituju',
        'jabatan_yang_dituju',
        'foto',
        'nomor_antrian',
        'tanggal_kunjungan',
        'waktu_masuk',
        'waktu_keluar',
        'status',
        'catatan',
    ];

    protected $appends = ['foto_url'];

    protected function casts(): array
    {
        return [
            'tanggal_kunjungan' => 'date',
            'waktu_masuk'       => 'datetime',
            'waktu_keluar'      => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $tamu) {
            $today = Carbon::today()->toDateString();

            // Count tamu already registered today to determine next queue number
            $countToday = static::where('tanggal_kunjungan', $today)->count();
            $tamu->nomor_antrian = 'A' . str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
        });
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto
            ? Storage::disk('public')->url($this->foto)
            : null;
    }
}

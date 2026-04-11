<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jumbotron extends Model
{
    protected $fillable = [
        'judul',
        'deskripsi',
        'gambar',
        'urutan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'urutan'    => 'integer',
        ];
    }
}

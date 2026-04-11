<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layanans', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', [
                'penganggaran',
                'penatausahaan',
                'pelaporan_pertanggungjawaban',
                'perencanaan_evaluasi',
                'perjanjian_kinerja',
            ]);
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->unsignedSmallInteger('tahun_apbd');
            $table->string('file_dokumen')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layanans');
    }
};

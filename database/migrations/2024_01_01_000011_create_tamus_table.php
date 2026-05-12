<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tamus', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('instansi_asal')->nullable();
            $table->string('no_identitas')->nullable();
            $table->enum('jenis_identitas', ['ktp', 'sim', 'paspor', 'lainnya'])->nullable();
            $table->text('keperluan');
            $table->string('nama_yang_dituju');
            $table->string('jabatan_yang_dituju')->nullable();
            $table->string('foto')->nullable();
            $table->string('nomor_antrian', 10);
            $table->date('tanggal_kunjungan');
            $table->timestamp('waktu_masuk');
            $table->timestamp('waktu_keluar')->nullable();
            $table->enum('status', ['menunggu', 'diterima', 'ditolak', 'selesai'])->default('menunggu');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tamus');
    }
};

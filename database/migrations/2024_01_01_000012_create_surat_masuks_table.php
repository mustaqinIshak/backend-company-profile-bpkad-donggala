<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_masuks', function (Blueprint $table) {
            $table->id();
            $table->string('no_agenda')->unique()->comment('Format: SM/YYYY/NNN');
            $table->string('nomor_surat')->nullable()->comment('Nomor surat dari pengirim');
            $table->string('pengirim');
            $table->string('instansi_pengirim');
            $table->string('perihal');
            $table->date('tanggal_surat');
            $table->date('tanggal_terima');
            $table->string('file_surat')->nullable();
            $table->enum('status', ['baru', 'diproses', 'selesai', 'arsip'])->default('baru');
            $table->text('catatan')->nullable();
            $table->foreignId('diterima_oleh')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_masuks');
    }
};

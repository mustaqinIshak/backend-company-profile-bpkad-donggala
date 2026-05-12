<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_keluars', function (Blueprint $table) {
            $table->id();
            $table->string('no_agenda')->unique()->comment('Format: SK/YYYY/NNN');
            $table->string('nomor_surat')->nullable()->comment('Diisi saat surat resmi dikirim');
            $table->string('perihal');
            $table->string('tujuan');
            $table->string('instansi_tujuan');
            $table->date('tanggal_surat')->nullable();
            $table->date('tanggal_kirim')->nullable();
            $table->string('file_surat')->nullable();
            $table->enum('status', ['draft', 'menunggu_persetujuan', 'disetujui', 'dikirim', 'arsip'])->default('draft');
            $table->text('catatan')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('admins');
            $table->foreignId('disetujui_oleh')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_keluars');
    }
};

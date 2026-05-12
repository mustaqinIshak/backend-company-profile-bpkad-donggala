<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposisis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_masuk_id')->constrained('surat_masuks')->cascadeOnDelete();
            $table->foreignId('dari_admin_id')->constrained('admins');
            $table->foreignId('kepada_admin_id')->constrained('admins');
            $table->text('instruksi');
            $table->text('catatan_balasan')->nullable();
            $table->enum('status', ['belum_diproses', 'sedang_diproses', 'selesai'])->default('belum_diproses');
            $table->date('tanggal_disposisi');
            $table->date('tanggal_selesai')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposisis');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisasis', function (Blueprint $table) {
            $table->id();
            $table->enum('bidang', ['sekretariat', 'aset', 'perbendaharaan', 'akuntansi', 'anggaran'])->unique();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('jabatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisasi_id')->constrained()->cascadeOnDelete();
            $table->string('nama_jabatan');
            $table->string('nama_pejabat');
            $table->string('nip', 30)->nullable();
            $table->string('foto')->nullable();
            $table->json('tugas_fungsi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatans');
        Schema::dropIfExists('organisasis');
    }
};

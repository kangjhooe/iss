<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset_tanda_tangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->enum('jenis', ['tanda_tangan', 'stempel']);
            $table->string('nama');
            $table->string('file_path');
            $table->string('pemilik_nama')->nullable();
            $table->string('pemilik_jabatan')->nullable();
            $table->string('pemilik_nip')->nullable();
            $table->unsignedSmallInteger('lebar_mm')->default(40);
            $table->unsignedSmallInteger('tinggi_mm')->default(20);
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();

            $table->index(['institution_id', 'jenis', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset_tanda_tangan');
    }
};

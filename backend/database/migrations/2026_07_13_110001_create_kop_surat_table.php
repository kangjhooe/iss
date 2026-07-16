<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kop_surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->string('nama');
            $table->string('logo_kiri')->nullable();
            $table->string('logo_kanan')->nullable();
            $table->string('baris_1')->nullable();
            $table->string('baris_2')->nullable();
            $table->string('baris_3')->nullable();
            $table->text('alamat')->nullable();
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->longText('isi_html')->nullable();
            $table->boolean('tampilkan_garis')->default(true);
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();

            $table->index(['institution_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kop_surat');
    }
};

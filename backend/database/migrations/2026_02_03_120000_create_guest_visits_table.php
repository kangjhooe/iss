<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Buku Tamu: catatan kunjungan tamu dengan foto (wajib).
     */
    public function up(): void
    {
        Schema::create('guest_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('nama_tamu', 255);
            $table->string('no_identitas', 64)->nullable();
            $table->string('instansi_asal', 255)->nullable();
            $table->string('no_telepon', 32)->nullable();
            $table->string('tujuan_kunjungan', 255);
            $table->string('orang_ditemui', 255)->nullable();
            $table->dateTime('waktu_masuk');
            $table->dateTime('waktu_keluar')->nullable();
            $table->string('foto_path', 500);
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'waktu_masuk']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_visits');
    }
};

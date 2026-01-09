<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('nip')->nullable(); // Nomor Induk Pegawai
            $table->string('nuptk')->unique()->nullable(); // Nomor Unik Pendidik dan Tenaga Kependidikan
            $table->string('name');
            $table->enum('gender', ['L', 'P']);
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('religion')->nullable();
            $table->enum('employment_status', ['PNS', 'CPNS', 'Guru Tetap Yayasan', 'Guru Honor Sekolah', 'Guru Kontrak'])->nullable();
            $table->enum('education_level', ['SMA', 'D3', 'S1', 'S2', 'S3'])->nullable();
            $table->string('major')->nullable(); // Jurusan pendidikan
            $table->string('subject')->nullable(); // Mata pelajaran yang diampu
            $table->enum('status', ['Aktif', 'Pensiun', 'Pindah', 'Tidak Aktif'])->default('Aktif');
            $table->date('join_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['institution_id', 'nip']);
            $table->index(['institution_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher');
    }
};

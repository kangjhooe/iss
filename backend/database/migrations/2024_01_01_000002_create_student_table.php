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
        Schema::create('student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('nis')->nullable(); // Nomor Induk Siswa
            $table->string('nisn')->unique()->nullable(); // Nomor Induk Siswa Nasional
            $table->string('name');
            $table->enum('gender', ['L', 'P']);
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('religion')->nullable();
            $table->string('class')->nullable(); // Kelas saat ini
            $table->string('academic_year')->nullable(); // Tahun ajaran
            $table->enum('status', ['Aktif', 'Lulus', 'Pindah', 'Drop Out', 'Tidak Aktif'])->default('Aktif');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['institution_id', 'nis']);
            $table->index(['institution_id', 'class']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student');
    }
};

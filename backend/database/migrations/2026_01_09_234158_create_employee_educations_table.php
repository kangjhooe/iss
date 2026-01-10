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
        Schema::create('employee_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');
            $table->enum('level', ['SD', 'SMP', 'SMA', 'SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'])->nullable();
            $table->string('school_name')->nullable(); // Nama sekolah/universitas
            $table->string('major')->nullable(); // Jurusan
            $table->year('graduation_year')->nullable(); // Tahun lulus
            $table->string('certificate_number')->nullable(); // Nomor ijazah
            $table->string('city')->nullable(); // Kota
            $table->text('notes')->nullable();
            $table->integer('order')->default(0); // Urutan (untuk sorting)
            $table->timestamps();
            
            $table->index('employee_id');
            $table->index(['employee_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_educations');
    }
};

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
        Schema::create('class_student_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('class')->onDelete('cascade');
            $table->string('academic_year'); // Tahun ajaran
            $table->date('start_date')->nullable(); // Tanggal mulai di kelas ini
            $table->date('end_date')->nullable(); // Tanggal pindah/keluar dari kelas ini
            $table->enum('status', ['Aktif', 'Pindah', 'Lulus', 'Drop Out'])->default('Aktif');
            $table->text('notes')->nullable(); // Catatan (alasan pindah, dll)
            $table->timestamps();
            
            // Indexes
            $table->index('student_id');
            $table->index('class_id');
            $table->index('academic_year');
            $table->index(['student_id', 'academic_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_student_history');
    }
};

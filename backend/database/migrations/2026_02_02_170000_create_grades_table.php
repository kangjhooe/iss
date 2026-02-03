<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Buku nilai: nilai per siswa per mapel per semester (UH, UTS, UAS, tugas, nilai akhir).
     */
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('class')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained('employee')->onDelete('set null')->comment('Guru penginput');
            $table->string('grade_type', 20)->comment('uh, uts, uas, tugas, nilai_akhir');
            $table->decimal('value', 5, 2)->comment('Nilai 0-100 atau skala lain');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'semester_id', 'grade_type'], 'grades_student_subject_semester_type_unique');
            $table->index(['institution_id', 'semester_id']);
            $table->index(['class_id', 'subject_id', 'semester_id']);
            $table->index(['student_id', 'semester_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};

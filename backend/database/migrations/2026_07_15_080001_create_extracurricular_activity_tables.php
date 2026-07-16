<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extracurricular_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('extracurricular_id')->constrained('extracurriculars')->onDelete('cascade');
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
            $table->date('session_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('topic', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('employee')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['extracurricular_id', 'session_date']);
            $table->index(['institution_id', 'semester_id']);
        });

        Schema::create('extracurricular_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('extracurricular_sessions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->string('status', 20)->default('hadir'); // hadir, izin, sakit, alpha
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'student_id'], 'ekskul_attendance_session_student_unique');
            $table->index('student_id');
        });

        Schema::create('extracurricular_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('extracurricular_id')->constrained('extracurriculars')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->decimal('score', 5, 2)->nullable();
            $table->string('predicate', 10)->nullable(); // A, B, C, D
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('employee')->onDelete('set null');
            $table->timestamps();

            $table->unique(['extracurricular_id', 'student_id', 'semester_id'], 'ekskul_grade_unique');
            $table->index(['institution_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracurricular_grades');
        Schema::dropIfExists('extracurricular_attendances');
        Schema::dropIfExists('extracurricular_sessions');
    }
};

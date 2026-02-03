<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Jurnal mengajar: catatan pertemuan mengajar per tanggal, kelas, mapel, guru.
     */
    public function up(): void
    {
        Schema::create('teaching_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');
            $table->foreignId('lesson_schedule_id')->nullable()->constrained('lesson_schedules')->onDelete('set null');
            $table->foreignId('class_id')->constrained('class')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');
            $table->date('journal_date');
            $table->unsignedSmallInteger('period')->default(1)->comment('Jam ke (1, 2, 3, ...)');
            $table->text('material_taught')->nullable()->comment('Materi yang diajarkan');
            $table->text('attendance_notes')->nullable()->comment('Catatan kehadiran siswa');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'semester_id']);
            $table->index(['journal_date', 'employee_id']);
            $table->index(['class_id', 'journal_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_journals');
    }
};

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
        Schema::create('lesson_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('class')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');
            $table->foreignId('room_id')->nullable()->constrained('room')->onDelete('set null');
            $table->unsignedTinyInteger('day_of_week')->comment('1=Senin, 2=Selasa, ..., 5=Jumat');
            $table->unsignedSmallInteger('period')->comment('Jam ke (1, 2, 3, ...)');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['semester_id', 'class_id', 'day_of_week', 'period'], 'lesson_schedules_slot_unique');
            $table->index(['institution_id', 'semester_id']);
            $table->index(['semester_id', 'class_id']);
            $table->index(['semester_id', 'employee_id']);
            $table->index(['semester_id', 'room_id', 'day_of_week', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_schedules');
    }
};

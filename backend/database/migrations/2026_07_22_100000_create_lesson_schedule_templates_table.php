<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_schedule_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->string('name', 100)->default('Default');
            $table->boolean('is_default')->default(false);
            // [{ day_of_week: 1-7, periods: 0-20, is_holiday: bool }, ...]
            $table->json('days');
            $table->timestamps();

            $table->unique(
                ['institution_id', 'semester_id', 'name'],
                'lesson_schedule_templates_inst_sem_name_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_schedule_templates');
    }
};

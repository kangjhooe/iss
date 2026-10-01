<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extracurriculars', function (Blueprint $table) {
            if (!Schema::hasColumn('extracurriculars', 'assessment_mode')) {
                $table->string('assessment_mode', 32)->default('standard')->after('is_pramuka');
            }
        });

        if (!Schema::hasTable('quran_surahs')) {
            Schema::create('quran_surahs', function (Blueprint $table) {
                $table->unsignedTinyInteger('number')->primary();
                $table->string('name_ar', 100);
                $table->string('name_id', 100);
                $table->string('name_latin', 100)->nullable();
                $table->unsignedSmallInteger('ayah_count');
                $table->unsignedTinyInteger('revelation_order')->nullable();
                $table->string('revelation_type', 20)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('quran_ayahs')) {
            Schema::create('quran_ayahs', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('surah_number');
                $table->unsignedSmallInteger('ayah_number');
                $table->text('text_ar')->nullable();
                $table->text('text_id')->nullable();
                $table->timestamps();

                $table->unique(['surah_number', 'ayah_number'], 'quran_ayahs_surah_ayah_unique');
                $table->foreign('surah_number')->references('number')->on('quran_surahs')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('memorization_targets')) {
            Schema::create('memorization_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
                $table->foreignId('extracurricular_id')->constrained('extracurriculars')->cascadeOnDelete();
                $table->string('scope', 20); // extracurricular | class | student
                $table->foreignId('class_id')->nullable()->constrained('class')->nullOnDelete();
                $table->foreignId('student_id')->nullable()->constrained('student')->nullOnDelete();
                $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->string('name', 255)->nullable();
                $table->json('items');
                $table->timestamps();

                $table->index(['extracurricular_id', 'scope'], 'mem_target_ekskul_scope_idx');
                $table->index(['extracurricular_id', 'student_id'], 'mem_target_ekskul_student_idx');
                $table->index(['extracurricular_id', 'class_id'], 'mem_target_ekskul_class_idx');
            });
        }

        if (!Schema::hasTable('memorization_deposits')) {
            Schema::create('memorization_deposits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
                $table->foreignId('extracurricular_id')->constrained('extracurriculars')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('student')->cascadeOnDelete();
                $table->foreignId('session_id')->nullable()->constrained('extracurricular_sessions')->nullOnDelete();
                $table->unsignedTinyInteger('surah_number');
                $table->unsignedSmallInteger('ayah_from');
                $table->unsignedSmallInteger('ayah_to');
                $table->string('quality', 20)->nullable(); // lancar | kurang | mengulang
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('employee')->nullOnDelete();
                $table->timestamp('deposited_at')->useCurrent();
                $table->timestamps();

                $table->index(['extracurricular_id', 'student_id'], 'mem_deposit_ekskul_student_idx');
                $table->index(['surah_number', 'ayah_from', 'ayah_to'], 'mem_deposit_range_idx');
            });
        }

        if (!Schema::hasTable('memorization_ayah_progress')) {
            Schema::create('memorization_ayah_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('extracurricular_id')->constrained('extracurriculars')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('student')->cascadeOnDelete();
                $table->unsignedTinyInteger('surah_number');
                $table->unsignedSmallInteger('ayah_number');
                $table->string('status', 20)->default('deposited');
                $table->foreignId('last_deposit_id')->nullable()->constrained('memorization_deposits')->nullOnDelete();
                $table->timestamp('deposited_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['extracurricular_id', 'student_id', 'surah_number', 'ayah_number'],
                    'mem_ayah_progress_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('memorization_ayah_progress');
        Schema::dropIfExists('memorization_deposits');
        Schema::dropIfExists('memorization_targets');
        Schema::dropIfExists('quran_ayahs');
        Schema::dropIfExists('quran_surahs');

        if (Schema::hasColumn('extracurriculars', 'assessment_mode')) {
            Schema::table('extracurriculars', function (Blueprint $table) {
                $table->dropColumn('assessment_mode');
            });
        }
    }
};

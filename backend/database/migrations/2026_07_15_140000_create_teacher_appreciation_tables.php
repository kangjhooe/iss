<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_achievement_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('code', 50)->nullable();
            $table->integer('point_value')->default(0);
            $table->string('category', 50)->nullable()->comment('akademik, pengembangan, pengabdian, inovasi, kedisiplinan');
            $table->json('level_multipliers')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['institution_id', 'is_active']);
        });

        Schema::create('teacher_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');
            $table->foreignId('achievement_type_id')->constrained('teacher_achievement_types')->onDelete('restrict');
            $table->string('title', 255)->nullable();
            $table->date('achievement_date');
            $table->integer('point_value');
            $table->string('level', 30)->nullable()->comment('sekolah, kabupaten, provinsi, nasional, internasional');
            $table->text('notes')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('status', 20)->default('approved')->comment('pending, approved, rejected');
            $table->foreignId('submitted_by')->nullable()->constrained('user')->onDelete('set null');
            $table->foreignId('given_by')->nullable()->constrained('user')->onDelete('set null');
            $table->foreignId('reviewed_by')->nullable()->constrained('user')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
            $table->timestamps();

            $table->index(['institution_id', 'achievement_date']);
            $table->index(['employee_id', 'status']);
            $table->index(['institution_id', 'status']);
            $table->index(['academic_year_id', 'semester_id']);
        });

        Schema::create('teacher_point_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->integer('point_min');
            $table->integer('point_max');
            $table->string('reward_name', 255);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['institution_id', 'is_active']);
        });

        Schema::create('teacher_reward_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');
            $table->foreignId('teacher_point_reward_id')->nullable()->constrained('teacher_point_rewards')->onDelete('set null');
            $table->string('reward_name', 255);
            $table->date('reward_date');
            $table->integer('score_at_reward')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('user')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
            $table->timestamps();
            $table->index(['employee_id', 'reward_date']);
            $table->index(['institution_id', 'academic_year_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_reward_logs');
        Schema::dropIfExists('teacher_point_rewards');
        Schema::dropIfExists('teacher_achievements');
        Schema::dropIfExists('teacher_achievement_types');
    }
};

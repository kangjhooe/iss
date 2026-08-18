<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uks_visit_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['institution_id', 'is_active']);
        });

        Schema::create('uks_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->foreignId('recorded_by')->constrained('user')->onDelete('cascade');
            $table->foreignId('uks_visit_type_id')->nullable()->constrained('uks_visit_types')->onDelete('set null');
            $table->date('visit_date');
            $table->enum('status', ['selesai', 'observasi', 'rujuk'])->default('selesai');
            $table->text('complaint')->nullable();
            $table->text('action_taken')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->string('blood_pressure', 20)->nullable();
            $table->decimal('temperature_c', 4, 1)->nullable();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
            $table->foreignId('class_id')->nullable()->constrained('class')->onDelete('set null');
            $table->timestamps();

            $table->index(['institution_id', 'visit_date']);
            $table->index(['student_id', 'visit_date']);
            $table->index(['recorded_by', 'visit_date']);
            $table->index(['uks_visit_type_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uks_visits');
        Schema::dropIfExists('uks_visit_types');
    }
};

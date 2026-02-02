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
        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->foreignId('violation_type_id')->constrained('violation_types')->onDelete('restrict');
            $table->foreignId('reported_by')->constrained('user')->onDelete('cascade');
            $table->date('violation_date');
            $table->string('sanction', 255)->nullable();
            $table->enum('status', ['dicatat', 'sanksi_diberikan', 'follow_up', 'selesai'])->default('dicatat');
            $table->text('description')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
            $table->foreignId('class_id')->nullable()->constrained('class')->onDelete('set null');
            $table->timestamps();

            $table->index(['institution_id', 'violation_date']);
            $table->index(['student_id', 'violation_date']);
            $table->index(['violation_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('violations');
    }
};

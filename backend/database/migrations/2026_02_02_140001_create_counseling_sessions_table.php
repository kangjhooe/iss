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
        Schema::create('counseling_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->foreignId('counselor_id')->constrained('user')->onDelete('cascade');
            $table->foreignId('counseling_type_id')->nullable()->constrained('counseling_types')->onDelete('set null');
            $table->date('session_date');
            $table->enum('status', ['jadwal', 'berlangsung', 'selesai', 'dibatalkan'])->default('jadwal');
            $table->text('summary')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
            $table->foreignId('class_id')->nullable()->constrained('class')->onDelete('set null');
            $table->timestamps();

            $table->index(['institution_id', 'session_date']);
            $table->index(['student_id', 'session_date']);
            $table->index(['counselor_id', 'session_date']);
            $table->index(['counseling_type_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counseling_sessions');
    }
};

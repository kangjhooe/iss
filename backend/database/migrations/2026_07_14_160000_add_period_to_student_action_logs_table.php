<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_action_logs', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->after('notes')->constrained('academic_years')->onDelete('set null');
            $table->foreignId('semester_id')->nullable()->after('academic_year_id')->constrained('semesters')->onDelete('set null');
            $table->index(['institution_id', 'academic_year_id', 'student_id'], 'sal_inst_year_student_idx');
            $table->index(['student_id', 'point_threshold_id', 'academic_year_id'], 'sal_student_threshold_year_idx');
        });
    }

    public function down(): void
    {
        Schema::table('student_action_logs', function (Blueprint $table) {
            $table->dropIndex('sal_inst_year_student_idx');
            $table->dropIndex('sal_student_threshold_year_idx');
            $table->dropConstrainedForeignId('academic_year_id');
            $table->dropConstrainedForeignId('semester_id');
        });
    }
};

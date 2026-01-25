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
        // Add semester_id to student table
        Schema::table('student', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->after('academic_year_id')
                ->constrained('semesters')->onDelete('set null');
            $table->index('semester_id');
        });

        // Add semester_id to class table
        Schema::table('class', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->after('academic_year_id')
                ->constrained('semesters')->onDelete('set null');
            $table->index('semester_id');
        });

        // Add semester_id to class_student_history table
        Schema::table('class_student_history', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->after('academic_year_id')
                ->constrained('semesters')->onDelete('set null');
            $table->index('semester_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_student_history', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropIndex(['semester_id']);
            $table->dropColumn('semester_id');
        });

        Schema::table('class', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropIndex(['semester_id']);
            $table->dropColumn('semester_id');
        });

        Schema::table('student', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropIndex(['semester_id']);
            $table->dropColumn('semester_id');
        });
    }
};

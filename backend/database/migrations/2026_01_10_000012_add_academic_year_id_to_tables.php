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
        // Add academic_year_id to class table
        Schema::table('class', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->after('academic_year')
                ->constrained('academic_years')->onDelete('set null');
            $table->index('academic_year_id');
        });

        // Add academic_year_id to student table
        Schema::table('student', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->after('academic_year')
                ->constrained('academic_years')->onDelete('set null');
            $table->index('academic_year_id');
        });

        // Add academic_year_id to class_student_history table
        Schema::table('class_student_history', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->after('academic_year')
                ->constrained('academic_years')->onDelete('set null');
            $table->index('academic_year_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_student_history', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropIndex(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });

        Schema::table('student', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropIndex(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });

        Schema::table('class', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropIndex(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
    }
};

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
        Schema::table('institution', function (Blueprint $table) {
            $table->foreignId('active_academic_year_id')->nullable()->after('is_active')
                ->constrained('academic_years')->onDelete('set null');
            $table->foreignId('active_semester_id')->nullable()->after('active_academic_year_id')
                ->constrained('semesters')->onDelete('set null');
            $table->index('active_academic_year_id');
            $table->index('active_semester_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            $table->dropForeign(['active_semester_id']);
            $table->dropIndex(['active_semester_id']);
            $table->dropColumn('active_semester_id');
            
            $table->dropForeign(['active_academic_year_id']);
            $table->dropIndex(['active_academic_year_id']);
            $table->dropColumn('active_academic_year_id');
        });
    }
};

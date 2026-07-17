<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_achievements', function (Blueprint $table) {
            $table->index(
                ['institution_id', 'academic_year_id', 'semester_id', 'achievement_date', 'id'],
                'ta_inst_period_date_idx'
            );
            $table->index(
                ['institution_id', 'status', 'achievement_date', 'id'],
                'ta_inst_status_date_idx'
            );
        });

        Schema::table('teacher_violations', function (Blueprint $table) {
            $table->index(
                ['institution_id', 'academic_year_id', 'semester_id', 'violation_date', 'id'],
                'tv_inst_period_date_idx'
            );
            $table->index(
                ['institution_id', 'status', 'violation_date', 'id'],
                'tv_inst_status_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('teacher_achievements', function (Blueprint $table) {
            $table->dropIndex('ta_inst_period_date_idx');
            $table->dropIndex('ta_inst_status_date_idx');
        });

        Schema::table('teacher_violations', function (Blueprint $table) {
            $table->dropIndex('tv_inst_period_date_idx');
            $table->dropIndex('tv_inst_status_date_idx');
        });
    }
};

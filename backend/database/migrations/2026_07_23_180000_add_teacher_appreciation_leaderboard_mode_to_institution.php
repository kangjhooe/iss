<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            if (!Schema::hasColumn('institution', 'teacher_appreciation_leaderboard_mode')) {
                $table->string('teacher_appreciation_leaderboard_mode', 20)
                    ->default('guru_only')
                    ->after('location_radius');
            }
        });
    }

    public function down(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            if (Schema::hasColumn('institution', 'teacher_appreciation_leaderboard_mode')) {
                $table->dropColumn('teacher_appreciation_leaderboard_mode');
            }
        });
    }
};

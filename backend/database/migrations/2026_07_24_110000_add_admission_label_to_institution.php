<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            $table->string('admission_label', 50)->nullable()->after('teacher_appreciation_leaderboard_mode');
        });
    }

    public function down(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            $table->dropColumn('admission_label');
        });
    }
};

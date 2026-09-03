<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievement_types', function (Blueprint $table) {
            $table->json('level_point_values')->nullable()->after('point_value')
                ->comment('Poin per tingkat lomba: sekolah, kabupaten, provinsi, nasional, internasional');
        });
    }

    public function down(): void
    {
        Schema::table('achievement_types', function (Blueprint $table) {
            $table->dropColumn('level_point_values');
        });
    }
};

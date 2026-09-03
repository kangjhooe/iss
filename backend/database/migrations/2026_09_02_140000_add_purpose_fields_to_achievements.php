<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievement_types', function (Blueprint $table) {
            $table->string('purpose', 30)->default('akreditasi')->after('category')
                ->comment('akreditasi | apresiasi');
            $table->index(['institution_id', 'purpose', 'is_active'], 'achievement_types_inst_purpose_active_idx');
        });

        Schema::table('achievements', function (Blueprint $table) {
            $table->string('purpose', 30)->default('akreditasi')->after('achievement_type_id')
                ->comment('akreditasi | apresiasi');
            $table->string('title', 255)->nullable()->after('purpose')
                ->comment('Nama lomba atau uraian kejadian');
            $table->string('level', 30)->nullable()->after('title')
                ->comment('sekolah, kabupaten, provinsi, nasional, internasional');
            $table->string('rank', 30)->nullable()->after('level')
                ->comment('juara_1, juara_2, juara_3, finalis, peserta, lainnya');
            $table->index(['institution_id', 'purpose', 'achievement_date'], 'achievements_inst_purpose_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropIndex('achievements_inst_purpose_date_idx');
            $table->dropColumn(['purpose', 'title', 'level', 'rank']);
        });

        Schema::table('achievement_types', function (Blueprint $table) {
            $table->dropIndex('achievement_types_inst_purpose_active_idx');
            $table->dropColumn('purpose');
        });
    }
};

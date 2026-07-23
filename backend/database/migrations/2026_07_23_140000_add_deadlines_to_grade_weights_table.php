<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deadline pengisian nilai per komponen (opsional, diatur guru mapel).
     */
    public function up(): void
    {
        Schema::table('grade_weights', function (Blueprint $table) {
            $table->date('deadline_penilaian')->nullable()->after('assessment_count');
            $table->date('deadline_uts')->nullable()->after('deadline_penilaian');
            $table->date('deadline_uas')->nullable()->after('deadline_uts');
            $table->date('deadline_nilai_akhir')->nullable()->after('deadline_uas');
        });
    }

    public function down(): void
    {
        Schema::table('grade_weights', function (Blueprint $table) {
            $table->dropColumn([
                'deadline_penilaian',
                'deadline_uts',
                'deadline_uas',
                'deadline_nilai_akhir',
            ]);
        });
    }
};

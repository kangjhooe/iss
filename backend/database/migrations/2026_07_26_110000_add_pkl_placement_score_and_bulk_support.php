<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penilaian sederhana pada penempatan PKL (nilai akhir + catatan penilaian).
     * Jurnal/monitoring tetap di pkl_monitoring_logs.
     */
    public function up(): void
    {
        Schema::table('pkl_placements', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable()->after('status');
            $table->text('assessment_notes')->nullable()->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('pkl_placements', function (Blueprint $table) {
            $table->dropColumn(['score', 'assessment_notes']);
        });
    }
};

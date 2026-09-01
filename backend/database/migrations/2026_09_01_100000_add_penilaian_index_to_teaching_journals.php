<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom penilaian harian yang dipakai per pertemuan (jurnal mengajar).
     */
    public function up(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->unsignedSmallInteger('penilaian_index')
                ->nullable()
                ->after('period')
                ->comment('Kolom penilaian harian (P1, P2, …) untuk pertemuan ini');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $table->dropColumn('penilaian_index');
        });
    }
};

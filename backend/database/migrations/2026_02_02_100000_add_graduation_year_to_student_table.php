<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tahun lulus untuk arsip alumni (diisi saat siswa diluluskan).
     */
    public function up(): void
    {
        Schema::table('student', function (Blueprint $table) {
            $table->year('graduation_year')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student', function (Blueprint $table) {
            $table->dropColumn('graduation_year');
        });
    }
};

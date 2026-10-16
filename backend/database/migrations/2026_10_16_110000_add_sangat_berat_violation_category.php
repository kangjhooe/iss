<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE violation_types MODIFY category ENUM('ringan', 'sedang', 'berat', 'sangat_berat') NOT NULL DEFAULT 'ringan'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('violation_types')->where('category', 'sangat_berat')->update(['category' => 'berat']);
        DB::statement("ALTER TABLE violation_types MODIFY category ENUM('ringan', 'sedang', 'berat') NOT NULL DEFAULT 'ringan'");
    }
};

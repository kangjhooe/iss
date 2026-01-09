<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify enum to include MAK
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `institution` MODIFY COLUMN `level` ENUM('TK', 'SD', 'SMP', 'SMA', 'SMK', 'MA', 'MAK', 'MTs', 'MI', 'PAUD') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert enum back to original (remove MAK)
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `institution` MODIFY COLUMN `level` ENUM('TK', 'SD', 'SMP', 'SMA', 'SMK', 'MA', 'MTs', 'MI', 'PAUD') NULL");
    }
};

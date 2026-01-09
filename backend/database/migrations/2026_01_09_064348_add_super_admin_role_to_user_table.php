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
        // Modify enum to include super_admin
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `user` MODIFY COLUMN `role` ENUM('admin', 'institution_admin', 'teacher', 'student', 'super_admin') DEFAULT 'institution_admin'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert enum back to original
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `user` MODIFY COLUMN `role` ENUM('admin', 'institution_admin', 'teacher', 'student') DEFAULT 'institution_admin'");
    }
};

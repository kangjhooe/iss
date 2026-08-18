<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add parent to role ENUM (MySQL). Keep staff if already present in runtime schema.
        try {
            DB::statement("ALTER TABLE `user` MODIFY COLUMN `role` ENUM('admin', 'institution_admin', 'teacher', 'staff', 'student', 'super_admin', 'parent') DEFAULT 'institution_admin'");
        } catch (\Throwable $e) {
            try {
                DB::statement("ALTER TABLE `user` MODIFY COLUMN `role` ENUM('admin', 'institution_admin', 'teacher', 'student', 'super_admin', 'parent') DEFAULT 'institution_admin'");
            } catch (\Throwable $e2) {
                // ignore — role may already include parent or DB is non-MySQL
            }
        }

        if (!Schema::hasTable('parent_links')) {
            Schema::create('parent_links', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('institution_id');
                $table->string('relation', 40)->nullable(); // ayah|ibu|wali|other
                $table->timestamps();

                $table->unique(['user_id', 'student_id']);
                $table->index(['institution_id', 'user_id']);
                $table->foreign('user_id')->references('id')->on('user')->cascadeOnDelete();
                $table->foreign('student_id')->references('id')->on('student')->cascadeOnDelete();
                $table->foreign('institution_id')->references('id')->on('institution')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_links');

        try {
            DB::statement("ALTER TABLE `user` MODIFY COLUMN `role` ENUM('admin', 'institution_admin', 'teacher', 'staff', 'student', 'super_admin') DEFAULT 'institution_admin'");
        } catch (\Throwable $e) {
            try {
                DB::statement("ALTER TABLE `user` MODIFY COLUMN `role` ENUM('admin', 'institution_admin', 'teacher', 'student', 'super_admin') DEFAULT 'institution_admin'");
            } catch (\Throwable $e2) {
                // ignore
            }
        }
    }
};

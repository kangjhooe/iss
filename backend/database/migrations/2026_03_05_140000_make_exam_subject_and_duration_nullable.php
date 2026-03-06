<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE exams MODIFY subject_id BIGINT UNSIGNED NULL, MODIFY duration_minutes SMALLINT UNSIGNED NULL');
        } else {
            Schema::table('exams', function (Blueprint $table) {
                $table->unsignedBigInteger('subject_id')->nullable()->change();
                $table->unsignedSmallInteger('duration_minutes')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE exams MODIFY subject_id BIGINT UNSIGNED NOT NULL, MODIFY duration_minutes SMALLINT UNSIGNED NOT NULL');
        } else {
            Schema::table('exams', function (Blueprint $table) {
                $table->unsignedBigInteger('subject_id')->nullable(false)->change();
                $table->unsignedSmallInteger('duration_minutes')->nullable(false)->change();
            });
        }
    }
};

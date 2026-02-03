<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mutasi ke sekolah luar sistem: target_institution_id boleh null,
     * isi target_npsn dan target_school_name (input manual).
     */
    public function up(): void
    {
        Schema::table('student_mutations', function (Blueprint $table) {
            $table->string('target_npsn', 20)->nullable()->after('target_institution_id');
            $table->string('target_school_name')->nullable()->after('target_npsn');
        });

        Schema::table('student_mutations', function (Blueprint $table) {
            $table->dropForeign(['target_institution_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE student_mutations MODIFY target_institution_id BIGINT UNSIGNED NULL');
        } else {
            DB::statement('ALTER TABLE student_mutations ALTER COLUMN target_institution_id DROP NOT NULL');
        }

        Schema::table('student_mutations', function (Blueprint $table) {
            $table->foreign('target_institution_id')->references('id')->on('institution')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_mutations', function (Blueprint $table) {
            $table->dropForeign(['target_institution_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE student_mutations MODIFY target_institution_id BIGINT UNSIGNED NOT NULL');
        } else {
            DB::statement('ALTER TABLE student_mutations ALTER COLUMN target_institution_id SET NOT NULL');
        }

        Schema::table('student_mutations', function (Blueprint $table) {
            $table->foreign('target_institution_id')->references('id')->on('institution')->onDelete('cascade');
        });

        Schema::table('student_mutations', function (Blueprint $table) {
            $table->dropColumn(['target_npsn', 'target_school_name']);
        });
    }
};

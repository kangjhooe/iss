<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mutasi masuk dari sekolah yang belum terdaftar: origin_institution_id boleh null,
     * isi origin_npsn dan origin_school_name (input manual).
     */
    public function up(): void
    {
        Schema::table('student_mutations', function (Blueprint $table) {
            $table->string('origin_npsn', 20)->nullable()->after('origin_institution_id');
            $table->string('origin_school_name')->nullable()->after('origin_npsn');
        });

        Schema::table('student_mutations', function (Blueprint $table) {
            $table->dropForeign(['origin_institution_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE student_mutations MODIFY origin_institution_id BIGINT UNSIGNED NULL');
        } else {
            DB::statement('ALTER TABLE student_mutations ALTER COLUMN origin_institution_id DROP NOT NULL');
        }

        Schema::table('student_mutations', function (Blueprint $table) {
            $table->foreign('origin_institution_id')->references('id')->on('institution')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_mutations', function (Blueprint $table) {
            $table->dropForeign(['origin_institution_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE student_mutations MODIFY origin_institution_id BIGINT UNSIGNED NOT NULL');
        } else {
            DB::statement('ALTER TABLE student_mutations ALTER COLUMN origin_institution_id SET NOT NULL');
        }

        Schema::table('student_mutations', function (Blueprint $table) {
            $table->foreign('origin_institution_id')->references('id')->on('institution')->onDelete('cascade');
        });

        Schema::table('student_mutations', function (Blueprint $table) {
            $table->dropColumn(['origin_npsn', 'origin_school_name']);
        });
    }
};

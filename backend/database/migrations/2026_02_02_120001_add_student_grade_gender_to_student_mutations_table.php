<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menyimpan grade dan gender siswa saat mutasi disetujui (untuk laporan per kelas/L-P).
     */
    public function up(): void
    {
        Schema::table('student_mutations', function (Blueprint $table) {
            $table->unsignedTinyInteger('student_grade')->nullable()->after('student_id')->comment('Kelas siswa saat mutasi (dari class.grade)');
            $table->string('student_gender', 1)->nullable()->after('student_grade')->comment('L/P saat mutasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_mutations', function (Blueprint $table) {
            $table->dropColumn(['student_grade', 'student_gender']);
        });
    }
};

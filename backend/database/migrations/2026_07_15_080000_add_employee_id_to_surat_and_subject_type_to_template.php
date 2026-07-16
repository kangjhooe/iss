<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            if (!Schema::hasColumn('surat', 'employee_id')) {
                $table->foreignId('employee_id')
                    ->nullable()
                    ->after('student_id')
                    ->constrained('employee')
                    ->nullOnDelete();
            }
        });

        Schema::table('template_surat', function (Blueprint $table) {
            if (!Schema::hasColumn('template_surat', 'subject_type')) {
                $table->enum('subject_type', ['siswa', 'pegawai', 'umum'])
                    ->default('siswa')
                    ->after('letter_type_code');
            }
        });

        // Template keterangan aktif guru/pegawai
        if (Schema::hasColumn('template_surat', 'subject_type')) {
            DB::table('template_surat')
                ->where('kode', 'SKAG')
                ->update(['subject_type' => 'pegawai']);
        }
    }

    public function down(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            if (Schema::hasColumn('surat', 'employee_id')) {
                $table->dropConstrainedForeignId('employee_id');
            }
        });

        Schema::table('template_surat', function (Blueprint $table) {
            if (Schema::hasColumn('template_surat', 'subject_type')) {
                $table->dropColumn('subject_type');
            }
        });
    }
};

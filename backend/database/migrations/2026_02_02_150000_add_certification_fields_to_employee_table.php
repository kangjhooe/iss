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
        Schema::table('employee', function (Blueprint $table) {
            $table->enum('certification_status', ['Sudah', 'Belum'])->nullable()->after('notes')->comment('Status sertifikasi guru');
            $table->date('certification_date')->nullable()->after('certification_status')->comment('Tanggal sertifikasi');
            $table->string('teacher_registration_number', 50)->nullable()->after('certification_date')->comment('Nomor Registrasi Guru (NRG)');
            $table->string('certification_number', 100)->nullable()->after('teacher_registration_number')->comment('Nomor sertifikat pendidik');
            $table->string('certification_issuing_authority', 255)->nullable()->after('certification_number')->comment('Lembaga penerbit sertifikat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn([
                'certification_status',
                'certification_date',
                'teacher_registration_number',
                'certification_number',
                'certification_issuing_authority',
            ]);
        });
    }
};

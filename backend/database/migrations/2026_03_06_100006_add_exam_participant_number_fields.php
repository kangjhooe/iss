<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add province_code and district_code to institution (for nomor peserta kartu).
     * Add participant_order to exam_participants (nomor urut 3 digit dalam sesi).
     */
    public function up(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            $table->string('province_code', 2)->nullable()->after('province');
            $table->string('district_code', 2)->nullable()->after('district');
        });

        Schema::table('exam_participants', function (Blueprint $table) {
            $table->unsignedInteger('participant_order')->nullable()->after('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            $table->dropColumn(['province_code', 'district_code']);
        });

        Schema::table('exam_participants', function (Blueprint $table) {
            $table->dropColumn('participant_order');
        });
    }
};

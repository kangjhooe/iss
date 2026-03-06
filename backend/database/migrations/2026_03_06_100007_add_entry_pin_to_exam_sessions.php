<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode ujian 6 huruf kapital untuk siswa; diperbarui setiap 20 menit.
     */
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('entry_pin', 6)->nullable()->after('status');
            $table->dateTime('entry_pin_updated_at')->nullable()->after('entry_pin');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn(['entry_pin', 'entry_pin_updated_at']);
        });
    }
};

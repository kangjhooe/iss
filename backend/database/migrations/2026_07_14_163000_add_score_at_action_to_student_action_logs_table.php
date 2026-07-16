<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_action_logs', function (Blueprint $table) {
            $table->integer('score_at_action')->nullable()->after('notes')
                ->comment('Skor pelanggaran saat tindakan dicatat; dipakai untuk deteksi poin baru');
        });
    }

    public function down(): void
    {
        Schema::table('student_action_logs', function (Blueprint $table) {
            $table->dropColumn('score_at_action');
        });
    }
};

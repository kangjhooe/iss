<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('delivery_mode', 20)->default('proctored')->after('start_type');
            $table->unsignedSmallInteger('max_attempts')->nullable()->after('delivery_mode');
        });

        Schema::table('exam_participants', function (Blueprint $table) {
            $table->dateTime('last_seen_at')->nullable()->after('extra_seconds');
            $table->unsignedSmallInteger('attempt_number')->default(1)->after('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['delivery_mode', 'max_attempts']);
        });
        Schema::table('exam_participants', function (Blueprint $table) {
            $table->dropColumn(['last_seen_at', 'attempt_number']);
        });
    }
};

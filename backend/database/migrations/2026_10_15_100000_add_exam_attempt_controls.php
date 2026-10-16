<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_participants', function (Blueprint $table) {
            $table->unsignedInteger('extra_seconds')->default(0)->after('started_at');
            $table->json('flagged_question_ids')->nullable()->after('question_order');
        });
    }

    public function down(): void
    {
        Schema::table('exam_participants', function (Blueprint $table) {
            $table->dropColumn(['extra_seconds', 'flagged_question_ids']);
        });
    }
};

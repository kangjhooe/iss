<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            $table->json('matching_data')->nullable()->after('key_answer');
        });

        // MySQL: extend enum for new question types
        DB::statement("ALTER TABLE question_bank MODIFY COLUMN type ENUM('pg', 'isian', 'uraian', 'pg_kompleks', 'matching') NOT NULL");

        Schema::table('exam_answers', function (Blueprint $table) {
            $table->json('selected_option_ids')->nullable()->after('question_option_id'); // PG kompleks: [id1, id2, ...]
            $table->json('matching_answer')->nullable()->after('selected_option_ids');     // Mencocokkan: [{"left_id":"1","right_id":"2"}, ...]
        });
    }

    public function down(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            $table->dropColumn('matching_data');
        });
        DB::statement("ALTER TABLE question_bank MODIFY COLUMN type ENUM('pg', 'isian', 'uraian') NOT NULL");

        Schema::table('exam_answers', function (Blueprint $table) {
            $table->dropColumn(['selected_option_ids', 'matching_answer']);
        });
    }
};

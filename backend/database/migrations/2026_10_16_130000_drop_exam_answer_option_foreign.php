<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jawaban PG menyimpan id opsi dari snapshot paket.
     * Menghapus opsi di bank tidak boleh mengosongkan jawaban ujian yang sudah berjalan.
     */
    public function up(): void
    {
        if (! Schema::hasTable('exam_answers') || ! Schema::hasColumn('exam_answers', 'question_option_id')) {
            return;
        }

        try {
            Schema::table('exam_answers', function (Blueprint $table) {
                $table->dropForeign(['question_option_id']);
            });
        } catch (\Throwable) {
            // Constraint mungkin sudah tidak ada di lingkungan yang dimigrasi sebagian.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('exam_answers') || ! Schema::hasColumn('exam_answers', 'question_option_id')) {
            return;
        }

        Schema::table('exam_answers', function (Blueprint $table) {
            $table->foreign('question_option_id')
                ->references('id')
                ->on('question_options')
                ->nullOnDelete();
        });
    }
};

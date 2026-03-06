<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_participant_id')->constrained('exam_participants')->onDelete('cascade');
            $table->foreignId('question_bank_id')->constrained('question_bank')->onDelete('cascade');
            $table->text('answer_text')->nullable();
            $table->foreignId('question_option_id')->nullable()->constrained('question_options')->onDelete('set null');
            $table->decimal('score', 5, 2)->nullable(); // for uraian manual scoring
            $table->dateTime('saved_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_participant_id', 'question_bank_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
    }
};

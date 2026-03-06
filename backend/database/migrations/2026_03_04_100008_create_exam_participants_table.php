<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->string('login_token', 64)->unique();
            $table->json('question_order')->nullable(); // [question_bank_id, ...] order when started
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('score_max', 5, 2)->nullable();
            $table->boolean('score_released')->default(false);
            $table->dateTime('score_released_at')->nullable();
            $table->enum('status', ['registered', 'started', 'submitted'])->default('registered');
            $table->timestamps();

            $table->unique(['exam_session_id', 'student_id']);
            $table->index(['exam_session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_participants');
    }
};

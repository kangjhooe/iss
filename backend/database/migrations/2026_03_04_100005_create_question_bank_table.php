<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('stimulus_id')->nullable()->constrained('question_stimuli')->onDelete('set null');
            $table->enum('type', ['pg', 'isian', 'uraian']);
            $table->text('body');
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->string('key_answer', 500)->nullable(); // for pg: option key (A/B/C/D), for isian: exact string
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'subject_id']);
            $table->index('stimulus_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank');
    }
};

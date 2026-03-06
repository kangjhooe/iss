<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained('question_bank')->onDelete('cascade');
            $table->string('option_key', 5); // A, B, C, D, ...
            $table->text('body');
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['question_bank_id', 'option_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};

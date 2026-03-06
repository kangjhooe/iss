<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_stimuli', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('set null');
            $table->text('content');
            $table->string('type', 20)->default('text'); // text, image
            $table->string('attachment_path')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_stimuli');
    }
};

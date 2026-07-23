<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wali_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('author_user_id');
            $table->text('body');
            $table->timestamps();

            $table->index(['institution_id', 'class_id']);
            $table->index(['student_id', 'class_id']);
            $table->index('author_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wali_notes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_releases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('version')->nullable();
            $table->date('released_at');
            $table->json('items');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('user')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['published_at', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};

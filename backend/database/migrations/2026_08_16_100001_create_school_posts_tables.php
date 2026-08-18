<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('school_posts')) {
            Schema::create('school_posts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('institution_id');
                $table->enum('type', ['news', 'gallery']);
                $table->string('title');
                $table->string('slug');
                $table->longText('body')->nullable();
                $table->string('cover_path')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->boolean('is_published')->default(false);
                $table->unsignedInteger('sort')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['institution_id', 'slug']);
                $table->index(['institution_id', 'type', 'is_published', 'sort']);
                $table->foreign('institution_id')->references('id')->on('institution')->cascadeOnDelete();
                $table->foreign('created_by')->references('id')->on('user')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('school_post_images')) {
            Schema::create('school_post_images', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_post_id');
                $table->string('path');
                $table->string('caption')->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();

                $table->index(['school_post_id', 'sort']);
                $table->foreign('school_post_id')->references('id')->on('school_posts')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_post_images');
        Schema::dropIfExists('school_posts');
    }
};

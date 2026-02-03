<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('library_book_categories')->onDelete('restrict');
            $table->string('isbn', 30)->nullable();
            $table->string('title', 255);
            $table->string('author', 255)->nullable();
            $table->string('publisher', 255)->nullable();
            $table->year('year')->nullable();
            $table->string('language', 50)->nullable();
            $table->unsignedSmallInteger('pages')->nullable();
            $table->string('shelf_code', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('user')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('category_id');
            $table->index('title');
            $table->index('author');
            $table->index('isbn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_books');
    }
};

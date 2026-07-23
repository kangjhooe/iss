<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            $table->boolean('is_public_ebook')->default(false)->after('ebook_path');
            $table->unsignedInteger('ebook_view_count')->default(0)->after('is_public_ebook');
        });

        Schema::create('library_ebook_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('user')->nullOnDelete();
            $table->string('source', 20); // student|staff|public
            $table->string('visitor_key', 80);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->index(['book_id', 'visitor_key', 'viewed_at'], 'library_ebook_views_dedup_idx');
            $table->index(['institution_id', 'viewed_at']);
            $table->index(['book_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_ebook_views');

        Schema::table('library_books', function (Blueprint $table) {
            $table->dropColumn(['is_public_ebook', 'ebook_view_count']);
        });
    }
};

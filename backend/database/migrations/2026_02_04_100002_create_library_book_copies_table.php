<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('library_books')->onDelete('cascade');
            $table->string('copy_code', 50);
            $table->enum('status', ['Tersedia', 'Dipinjam', 'Rusak', 'Hilang'])->default('Tersedia');
            $table->enum('condition', ['Baik', 'Rusak Ringan', 'Rusak Berat'])->default('Baik');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['book_id', 'copy_code']);
            $table->index('book_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_book_copies');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('correspondence_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('name'); // Nama kategori (Surat Resmi, Surat Undangan, dll)
            $table->enum('type', ['masuk', 'keluar', 'internal']); // Tipe surat
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('institution_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correspondence_categories');
    }
};

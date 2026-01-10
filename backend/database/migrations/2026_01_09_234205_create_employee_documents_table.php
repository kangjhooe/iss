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
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');
            $table->string('name'); // Nama dokumen (KK, Ijazah, dll)
            $table->string('file_path'); // Path file di storage
            $table->string('file_name'); // Nama file asli
            $table->bigInteger('file_size'); // Ukuran file dalam bytes
            $table->string('mime_type')->default('application/pdf'); // MIME type
            $table->text('description')->nullable(); // Deskripsi dokumen
            $table->timestamps();
            
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};

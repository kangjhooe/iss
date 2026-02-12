<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppdb_applicant_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ppdb_applicant_id')->constrained('ppdb_applicants')->onDelete('cascade');
            $table->string('name'); // KK, Akte, SKHUN, Foto, dll
            $table->string('file_path');
            $table->string('file_name');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('ppdb_applicant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppdb_applicant_documents');
    }
};

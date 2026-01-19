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
        Schema::create('correspondence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->enum('type', ['masuk', 'keluar', 'internal']);
            $table->string('letter_number')->nullable(); // Nomor surat (untuk surat keluar/internal)
            $table->string('reference_number')->nullable(); // Nomor referensi (untuk surat masuk)
            $table->string('subject'); // Perihal
            $table->string('from')->nullable(); // Pengirim (untuk surat masuk)
            $table->string('to')->nullable(); // Penerima (untuk surat keluar)
            $table->date('date'); // Tanggal surat
            $table->date('received_date')->nullable(); // Tanggal terima (untuk surat masuk)
            $table->enum('priority', ['biasa', 'penting', 'sangat_penting'])->default('biasa');
            $table->enum('status', ['draft', 'pending', 'approved', 'sent', 'archived'])->default('draft');
            $table->foreignId('category_id')->nullable()->constrained('correspondence_categories')->onDelete('set null');
            $table->foreignId('created_by')->constrained('user')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('user')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('description')->nullable(); // Isi ringkas surat
            $table->string('file_path')->nullable(); // Path ke file PDF yang diupload
            $table->string('file_name')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Untuk soft delete
            
            $table->index('institution_id');
            $table->index('type');
            $table->index('status');
            $table->index('category_id');
            $table->index('created_by');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correspondence');
    }
};

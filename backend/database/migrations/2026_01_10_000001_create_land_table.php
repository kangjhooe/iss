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
        Schema::create('land', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('name'); // Nama tanah/lahan
            $table->string('certificate_number')->nullable(); // Nomor sertifikat
            $table->enum('certificate_type', ['SHM', 'SHGB', 'HGB', 'Hak Pakai', 'Tanpa Sertifikat'])->nullable();
            $table->decimal('area', 10, 2); // Luas dalam m²
            $table->string('location')->nullable(); // Lokasi
            $table->enum('status', ['Milik Sendiri', 'Sewa', 'Pinjam', 'Hak Pakai'])->default('Milik Sendiri');
            $table->date('acquisition_date')->nullable(); // Tanggal perolehan
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('institution_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('land');
    }
};

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
        Schema::create('room', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('building_id')->nullable()->constrained('building')->onDelete('set null');
            $table->string('name'); // Nama ruangan
            $table->string('code')->nullable(); // Kode ruangan
            $table->enum('type', [
                'Kelas', 
                'Laboratorium', 
                'Perpustakaan', 
                'Kantor', 
                'Aula', 
                'Musholla', 
                'Kantin', 
                'Toilet', 
                'Gudang', 
                'Lainnya'
            ])->default('Kelas');
            $table->integer('floor')->default(1); // Lantai
            $table->decimal('area', 10, 2)->nullable(); // Luas dalam m²
            $table->integer('capacity')->nullable(); // Kapasitas
            $table->enum('condition', ['Baik', 'Rusak Ringan', 'Rusak Sedang', 'Rusak Berat'])->default('Baik');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('institution_id');
            $table->index('building_id');
            $table->index('type');
            $table->index('condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room');
    }
};

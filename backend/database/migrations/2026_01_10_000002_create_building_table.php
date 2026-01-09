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
        Schema::create('building', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('land_id')->nullable()->constrained('land')->onDelete('set null');
            $table->string('name'); // Nama gedung
            $table->string('code')->nullable(); // Kode gedung
            $table->integer('floor_count')->default(1); // Jumlah lantai
            $table->decimal('building_area', 10, 2)->nullable(); // Luas bangunan dalam m²
            $table->enum('condition', ['Baik', 'Rusak Ringan', 'Rusak Sedang', 'Rusak Berat'])->default('Baik');
            $table->year('construction_year')->nullable(); // Tahun pembangunan
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('institution_id');
            $table->index('land_id');
            $table->index('condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building');
    }
};

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
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->enum('name', ['Ganjil', 'Genap']);
            $table->integer('order')->default(1); // 1 untuk Ganjil, 2 untuk Genap
            $table->date('start_date'); // Tanggal mulai semester
            $table->date('end_date'); // Tanggal akhir semester
            $table->enum('status', ['Aktif', 'Selesai', 'Draft'])->default('Draft');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('academic_year_id');
            $table->index('status');
            $table->index(['academic_year_id', 'name']);
            $table->index(['start_date', 'end_date']);
            
            // Unique constraint: satu tahun ajaran hanya bisa punya satu semester Ganjil dan satu Genap
            $table->unique(['academic_year_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};

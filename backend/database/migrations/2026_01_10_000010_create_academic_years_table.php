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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // Format: 2025/2026
            $table->string('name')->nullable(); // Nama lengkap (opsional)
            $table->date('start_date'); // Tanggal mulai tahun ajaran
            $table->date('end_date'); // Tanggal akhir tahun ajaran
            $table->enum('status', ['Aktif', 'Arsip', 'Draft'])->default('Draft');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};

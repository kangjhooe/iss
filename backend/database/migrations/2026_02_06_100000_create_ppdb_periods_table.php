<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppdb_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->string('name'); // Gelombang 1, PPDB 2026/2027, dll
            $table->string('level', 20)->nullable(); // SD, SMP, SMA, SMK, dll (untuk multi jenjang)
            $table->date('open_date');
            $table->date('close_date');
            $table->enum('status', ['draft', 'open', 'closed', 'finished'])->default('draft');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppdb_periods');
    }
};

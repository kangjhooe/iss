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
        Schema::create('institution', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('npsn')->unique()->nullable(); // Nomor Pokok Sekolah Nasional
            $table->string('nss')->nullable(); // Nomor Statistik Sekolah
            $table->enum('level', ['TK', 'SD', 'SMP', 'SMA', 'SMK', 'MA', 'MTs', 'MI', 'PAUD'])->nullable();
            $table->enum('type', ['Negeri', 'Swasta'])->default('Swasta');
            $table->text('address')->nullable();
            $table->string('village')->nullable();
            $table->string('sub_district')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('principal_name')->nullable();
            $table->string('principal_nip')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('npsn');
            $table->index('is_active');
            $table->index(['level', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution');
    }
};

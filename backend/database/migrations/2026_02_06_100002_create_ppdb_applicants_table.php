<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppdb_applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ppdb_period_id')->constrained('ppdb_periods')->onDelete('cascade');
            $table->foreignId('ppdb_channel_id')->constrained('ppdb_channels')->onDelete('restrict');
            $table->string('registration_number', 50)->unique(); // PPDB-2026-00001
            $table->enum('status', ['draft', 'submitted', 'verification', 'verified', 'rejected', 'passed', 'failed', 're_registration', 'cancelled'])->default('draft');

            // Data calon (mirip student)
            $table->string('name');
            $table->string('nik', 20)->nullable();
            $table->string('nisn', 20)->nullable();
            $table->enum('gender', ['L', 'P']);
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('religion')->nullable();
            $table->string('previous_school')->nullable();

            // Orang tua / wali
            $table->string('father_name')->nullable();
            $table->string('father_phone')->nullable();
            $table->string('father_nik', 20)->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_phone')->nullable();
            $table->string('mother_nik', 20)->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('guardian_relation')->nullable();

            // Verifikasi & catatan
            $table->boolean('documents_verified')->default(false);
            $table->text('verification_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['ppdb_period_id', 'status']);
            $table->index(['ppdb_period_id', 'ppdb_channel_id']);
            $table->index('nisn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppdb_applicants');
    }
};

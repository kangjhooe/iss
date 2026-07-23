<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mutasi guru (Employee type=Guru) antar institusi. Tidak ada batasan jenjang.
     * Identitas utama: NUPTK.
     */
    public function up(): void
    {
        Schema::create('teacher_mutations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('origin_institution_id')->nullable()->constrained('institution')->onDelete('cascade');
            $table->string('origin_npsn', 20)->nullable();
            $table->string('origin_school_name')->nullable();

            $table->foreignId('target_institution_id')->nullable()->constrained('institution')->onDelete('cascade');
            $table->string('target_npsn', 20)->nullable();
            $table->string('target_school_name')->nullable();

            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');
            $table->string('employee_nuptk', 32)->nullable();
            $table->string('employee_nip', 32)->nullable();
            $table->string('employee_gender', 1)->nullable();

            $table->enum('initiated_by', ['origin', 'target'])->comment('origin = sekolah asal mengajukan, target = sekolah tujuan menarik');
            $table->foreignId('requested_by')->constrained('user')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('user')->onDelete('set null');

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'cancel_pending'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancel_requested_at')->nullable();
            $table->foreignId('cancel_requested_by')->nullable()->constrained('user')->onDelete('set null');
            $table->text('cancel_rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['origin_institution_id', 'status']);
            $table->index(['target_institution_id', 'status']);
            $table->index(['employee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_mutations');
    }
};

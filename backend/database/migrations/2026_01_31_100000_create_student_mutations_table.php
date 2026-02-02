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
        Schema::create('student_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('target_institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->enum('initiated_by', ['origin', 'target'])->comment('origin = sekolah asal mengajukan, target = sekolah tujuan menarik');
            $table->foreignId('requested_by')->constrained('user')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('user')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['origin_institution_id', 'status']);
            $table->index(['target_institution_id', 'status']);
            $table->index(['student_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_mutations');
    }
};

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
        Schema::create('employee_institution_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employee')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->enum('assignment_type', ['non_induk'])->default('non_induk');
            $table->enum('status', ['pending', 'approved', 'rejected', 'ended'])->default('pending');
            $table->string('subject')->nullable();
            $table->string('assignment_title')->nullable();
            $table->text('assignment_notes')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('user')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->text('ended_reason')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'institution_id'], 'emp_inst_idx');
            $table->index(['institution_id', 'status'], 'inst_status_idx');
            $table->index(['employee_id', 'status'], 'emp_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_institution_assignments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_position_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('structural_position_id')->constrained('structural_positions')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institution_id', 'structural_position_id'], 'payroll_pos_allow_inst_pos_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_position_allowances');
    }
};

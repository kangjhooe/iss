<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->string('category', 30)->default('other'); // payroll, other
            $table->string('title');
            $table->decimal('amount', 15, 2);
            $table->dateTime('expense_date');
            $table->string('method', 20)->default('transfer'); // cash, transfer, other
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('source', 20)->default('manual'); // auto, manual
            $table->foreignId('payroll_run_id')->nullable()->constrained('payroll_runs')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();

            $table->unique('payroll_run_id', 'finance_expenses_payroll_run_uq');
            $table->index(['institution_id', 'expense_date'], 'finance_expenses_inst_date_idx');
            $table->index(['institution_id', 'category'], 'finance_expenses_inst_cat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_expenses');
    }
};

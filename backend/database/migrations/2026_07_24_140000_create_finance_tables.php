<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_fee_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('frequency', 20)->default('one_time'); // monthly, yearly, one_time, as_needed
            $table->string('scope', 20)->default('school'); // school, class, student
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['institution_id', 'code']);
            $table->index(['institution_id', 'is_active']);
        });

        Schema::create('finance_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained('finance_fee_types')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('student')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('class')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('title');
            $table->string('period_label', 64)->nullable(); // e.g. 2026-07; cancelled appends -c{id}
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('unpaid'); // unpaid, partial, paid, cancelled
            $table->text('notes')->nullable();
            $table->string('batch_key', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();

            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'fee_type_id']);
            $table->index(['institution_id', 'student_id']);
            $table->index(['institution_id', 'due_date']);
            $table->index(['batch_key']);
            $table->index(
                ['institution_id', 'fee_type_id', 'student_id', 'period_label'],
                'finance_invoices_period_idx'
            );
        });

        Schema::create('finance_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('finance_invoices')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->dateTime('paid_at');
            $table->string('method', 20)->default('cash'); // cash, transfer, other
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();

            $table->index(['institution_id', 'paid_at']);
            $table->index(['invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payments');
        Schema::dropIfExists('finance_invoices');
        Schema::dropIfExists('finance_fee_types');
    }
};

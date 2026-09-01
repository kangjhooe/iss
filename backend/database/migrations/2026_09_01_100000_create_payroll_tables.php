<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 20); // earning | deduction
            $table->string('calc_mode', 30)->default('fixed'); // fixed | per_alpha_day | manual
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['institution_id', 'code'], 'payroll_comp_inst_code_uq');
            $table->index(['institution_id', 'is_active'], 'payroll_comp_inst_active_idx');
        });

        Schema::create('payroll_employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employee')->cascadeOnDelete();
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->string('payment_method', 20)->default('transfer');
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->date('effective_from')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['institution_id', 'employee_id'], 'payroll_emp_prof_inst_emp_uq');
        });

        Schema::create('payroll_employee_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employee')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('payroll_components')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['employee_id', 'component_id'], 'payroll_emp_comp_uq');
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('label');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedTinyInteger('working_days')->nullable();
            $table->string('status', 20)->default('open'); // open | closed
            $table->timestamps();

            $table->unique(['institution_id', 'year', 'month'], 'payroll_period_inst_ym_uq');
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->uuid('batch_key');
            $table->string('label')->nullable();
            $table->string('status', 20)->default('draft'); // draft | finalized | paid
            $table->json('employee_filter')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'status'], 'payroll_run_inst_status_idx');
        });

        Schema::create('payroll_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employee')->cascadeOnDelete();
            $table->decimal('gross', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net', 15, 2)->default(0);
            $table->json('attendance_snapshot')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['run_id', 'employee_id'], 'payroll_slip_run_emp_uq');
        });

        Schema::create('payroll_slip_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slip_id')->constrained('payroll_slips')->cascadeOnDelete();
            $table->foreignId('component_id')->nullable()->constrained('payroll_components')->nullOnDelete();
            $table->string('label');
            $table->string('type', 20); // earning | deduction
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('is_manual_override')->default(false);
            $table->string('source', 20)->default('rule'); // rule | attendance | manual | ad_hoc
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['slip_id', 'sort_order'], 'payroll_slip_line_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_slip_lines');
        Schema::dropIfExists('payroll_slips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('payroll_employee_components');
        Schema::dropIfExists('payroll_employee_profiles');
        Schema::dropIfExists('payroll_components');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_slips')) {
            return;
        }

        Schema::table('payroll_slips', function (Blueprint $table) {
            $table->unique(['period_id', 'employee_id'], 'payroll_slips_period_employee_uq');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payroll_slips')) {
            return;
        }

        Schema::table('payroll_slips', function (Blueprint $table) {
            $table->dropUnique('payroll_slips_period_employee_uq');
        });
    }
};

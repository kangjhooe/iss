<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_runs')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                if (! $this->indexExists('payroll_runs', 'payroll_runs_period_id_idx')) {
                    $table->index('period_id', 'payroll_runs_period_id_idx');
                }
            });
        }

        if (Schema::hasTable('payroll_slips')) {
            Schema::table('payroll_slips', function (Blueprint $table) {
                if (! $this->indexExists('payroll_slips', 'payroll_slips_emp_status_idx')) {
                    $table->index(['employee_id', 'status'], 'payroll_slips_emp_status_idx');
                }
                if (! $this->indexExists('payroll_slips', 'payroll_slips_period_emp_idx')) {
                    $table->index(['period_id', 'employee_id'], 'payroll_slips_period_emp_idx');
                }
            });
        }

        if (Schema::hasTable('finance_expenses')) {
            Schema::table('finance_expenses', function (Blueprint $table) {
                if (! $this->indexExists('finance_expenses', 'finance_expenses_inst_source_idx')) {
                    $table->index(['institution_id', 'source'], 'finance_expenses_inst_source_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_runs')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->dropIndex('payroll_runs_period_id_idx');
            });
        }
        if (Schema::hasTable('payroll_slips')) {
            Schema::table('payroll_slips', function (Blueprint $table) {
                $table->dropIndex('payroll_slips_emp_status_idx');
                $table->dropIndex('payroll_slips_period_emp_idx');
            });
        }
        if (Schema::hasTable('finance_expenses')) {
            Schema::table('finance_expenses', function (Blueprint $table) {
                $table->dropIndex('finance_expenses_inst_source_idx');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($row) => ($row->name ?? '') === $index);
        }

        $db = $connection->getDatabaseName();
        $result = $connection->select(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $index]
        );

        return (int) ($result[0]->c ?? 0) > 0;
    }
};

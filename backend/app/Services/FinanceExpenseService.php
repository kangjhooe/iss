<?php

namespace App\Services;

use App\Models\FinanceExpense;
use App\Models\PayrollRun;
use App\Models\PayrollSlip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceExpenseService
{
    public function recordPayrollRunExpense(PayrollRun $run, int $userId): ?FinanceExpense
    {
        $existing = FinanceExpense::query()
            ->where('payroll_run_id', $run->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $run->load(['period']);
        $totalNet = (float) PayrollSlip::query()
            ->where('run_id', $run->id)
            ->sum('net');

        if ($totalNet <= 0) {
            return null;
        }

        $slipCount = PayrollSlip::query()->where('run_id', $run->id)->count();
        $periodLabel = $run->period?->label ?? '—';
        $title = $run->label ?: ('Gaji ' . $periodLabel);

        try {
            return FinanceExpense::create([
                'institution_id' => $run->institution_id,
                'category' => FinanceExpense::CATEGORY_PAYROLL,
                'title' => $title,
                'amount' => round($totalNet, 2),
                'expense_date' => $run->paid_at ?? now(),
                'method' => 'transfer',
                'reference' => 'PAYROLL-' . $run->id,
                'notes' => "Otomatis dari penggajian #{$run->id} · {$slipCount} pegawai · periode {$periodLabel}",
                'source' => FinanceExpense::SOURCE_AUTO,
                'payroll_run_id' => $run->id,
                'recorded_by' => $userId,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return FinanceExpense::query()->where('payroll_run_id', $run->id)->first();
            }

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordManualExpense(int $institutionId, int $userId, array $data): FinanceExpense
    {
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pengeluaran harus lebih dari 0.',
            ]);
        }

        return FinanceExpense::create([
            'institution_id' => $institutionId,
            'category' => FinanceExpense::CATEGORY_OTHER,
            'title' => trim((string) $data['title']),
            'amount' => round($amount, 2),
            'expense_date' => $data['expense_date'] ?? now(),
            'method' => $data['method'] ?? 'cash',
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'source' => FinanceExpense::SOURCE_MANUAL,
            'payroll_run_id' => null,
            'recorded_by' => $userId,
        ]);
    }

    public function deleteManualExpense(FinanceExpense $expense): void
    {
        if ($expense->source === FinanceExpense::SOURCE_AUTO) {
            throw ValidationException::withMessages([
                'expense' => 'Pengeluaran otomatis dari penggajian tidak dapat dihapus manual.',
            ]);
        }

        $expense->delete();
    }

    public function voidPayrollRunExpense(PayrollRun $run): void
    {
        FinanceExpense::query()
            ->where('payroll_run_id', $run->id)
            ->where('source', FinanceExpense::SOURCE_AUTO)
            ->delete();
    }
}

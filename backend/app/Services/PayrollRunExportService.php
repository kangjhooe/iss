<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\PayrollRun;
use App\Models\PayrollSlip;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PayrollRunExportService
{
    /**
     * @return array{
     *   run: PayrollRun,
     *   institution: ?Institution,
     *   slips: Collection<int, PayrollSlip>,
     *   totals: array{gross: float, deductions: float, net: float, count: int}
     * }
     */
    public function buildReport(PayrollRun $run): array
    {
        $run->load(['period', 'creator:id,name', 'institution']);

        $slips = PayrollSlip::query()
            ->where('run_id', $run->id)
            ->with([
                'employee:id,nip,name,type,employment_status',
                'lines' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->join('employee', 'employee.id', '=', 'payroll_slips.employee_id')
            ->orderBy('employee.name')
            ->select('payroll_slips.*')
            ->get();

        $totals = [
            'gross' => round($slips->sum(fn (PayrollSlip $s) => (float) $s->gross), 2),
            'deductions' => round($slips->sum(fn (PayrollSlip $s) => (float) $s->total_deductions), 2),
            'net' => round($slips->sum(fn (PayrollSlip $s) => (float) $s->net), 2),
            'count' => $slips->count(),
        ];

        return [
            'run' => $run,
            'institution' => $run->institution ?? Institution::find($run->institution_id),
            'slips' => $slips,
            'totals' => $totals,
        ];
    }

    public function buildFilename(PayrollRun $run, string $extension): string
    {
        $label = Str::slug($run->label ?: $run->period?->label ?: 'gaji');

        return 'Rekap_Gaji_' . $label . '_' . date('Ymd_His') . '.' . $extension;
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'finalized' => 'Final',
            'paid' => 'Dibayar',
            default => $status,
        };
    }
}

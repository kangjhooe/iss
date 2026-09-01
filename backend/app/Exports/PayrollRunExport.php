<?php

namespace App\Exports;

use App\Models\PayrollSlip;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollRunExport implements WithMultipleSheets
{
    /**
     * @param  array{
     *   run: \App\Models\PayrollRun,
     *   slips: Collection<int, PayrollSlip>,
     *   totals: array{gross: float, deductions: float, net: float, count: int}
     * }  $report
     */
    public function __construct(protected array $report)
    {
    }

    public function sheets(): array
    {
        return [
            new PayrollRunSummarySheet($this->report),
            new PayrollRunSlipsSheet($this->report['slips'] ?? collect()),
            new PayrollRunLinesSheet($this->report['slips'] ?? collect()),
        ];
    }
}

class PayrollRunSummarySheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(protected array $report) {}

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function headings(): array
    {
        return ['Keterangan', 'Nilai'];
    }

    public function collection(): Collection
    {
        $run = $this->report['run'];
        $period = $run->period;
        $totals = $this->report['totals'];

        return collect([
            ['Proses', $run->label ?? '—'],
            ['Periode', $period?->label ?? '—'],
            ['Tanggal periode', ($period?->start_date?->format('Y-m-d') ?? '') . ' s/d ' . ($period?->end_date?->format('Y-m-d') ?? '')],
            ['Status', match ($run->status) {
                'draft' => 'Draft',
                'finalized' => 'Final',
                'paid' => 'Dibayar',
                default => $run->status,
            }],
            ['Jumlah slip', (int) ($totals['count'] ?? 0)],
            ['Total bruto', (float) ($totals['gross'] ?? 0)],
            ['Total potongan', (float) ($totals['deductions'] ?? 0)],
            ['Total net', (float) ($totals['net'] ?? 0)],
            ['Digenerate', $run->generated_at?->format('Y-m-d H:i') ?? '—'],
            ['Finalisasi', $run->finalized_at?->format('Y-m-d H:i') ?? '—'],
            ['Dibayar', $run->paid_at?->format('Y-m-d H:i') ?? '—'],
        ]);
    }
}

class PayrollRunSlipsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(protected Collection $slips) {}

    public function title(): string
    {
        return 'Rekap Slip';
    }

    public function headings(): array
    {
        return [
            'No',
            'NIP',
            'Nama',
            'Jenis',
            'Status Kepegawaian',
            'Bruto',
            'Potongan',
            'Net',
            'Alpha',
            'Cuti Tanpa Gaji',
            'Status Slip',
            'Catatan',
        ];
    }

    public function collection(): Collection
    {
        return $this->slips->values()->map(function (PayrollSlip $slip, int $index) {
            $snap = $slip->attendance_snapshot ?? [];

            return [
                $index + 1,
                $slip->employee?->nip ?? '',
                $slip->employee?->name ?? '',
                $slip->employee?->type ?? '',
                $slip->employee?->employment_status ?? '',
                (float) $slip->gross,
                (float) $slip->total_deductions,
                (float) $slip->net,
                (int) ($snap['alpha'] ?? 0),
                (int) ($snap['tanpa_gaji'] ?? 0),
                match ($slip->status) {
                    'draft' => 'Draft',
                    'final' => 'Final',
                    'paid' => 'Dibayar',
                    default => $slip->status,
                },
                $slip->notes ?? '',
            ];
        });
    }
}

class PayrollRunLinesSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(protected Collection $slips) {}

    public function title(): string
    {
        return 'Detail Komponen';
    }

    public function headings(): array
    {
        return [
            'NIP',
            'Nama',
            'Komponen',
            'Jenis',
            'Nominal',
            'Sumber',
            'Manual',
        ];
    }

    public function collection(): Collection
    {
        $rows = collect();

        foreach ($this->slips as $slip) {
            foreach ($slip->lines as $line) {
                $rows->push([
                    $slip->employee?->nip ?? '',
                    $slip->employee?->name ?? '',
                    $line->label,
                    $line->type === 'earning' ? 'Pendapatan' : 'Potongan',
                    (float) $line->amount,
                    $line->source,
                    $line->is_manual_override ? 'Ya' : 'Tidak',
                ]);
            }
        }

        return $rows;
    }
}

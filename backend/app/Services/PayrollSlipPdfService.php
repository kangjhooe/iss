<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\PayrollSlip;
use App\Support\StandardLetterhead;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollSlipPdfService
{
    public function stream(PayrollSlip $slip)
    {
        $slip->load([
            'employee:id,institution_id,nip,name,type,employment_status',
            'period',
            'lines' => fn ($q) => $q->orderBy('sort_order'),
            'institution',
        ]);

        $institution = $slip->institution ?? Institution::find($slip->institution_id);
        $letterheadHtml = StandardLetterhead::renderHtml($institution, true);

        $earnings = $slip->lines->where('type', 'earning')->values();
        $deductions = $slip->lines->where('type', 'deduction')->values();
        $maxRows = max($earnings->count(), $deductions->count(), 1);

        $pdf = Pdf::loadView('payroll.slip', [
            'institution' => $institution,
            'letterheadHtml' => $letterheadHtml,
            'slip' => $slip,
            'employee' => $slip->employee,
            'period' => $slip->period,
            'earnings' => $earnings,
            'deductions' => $deductions,
            'maxRows' => $maxRows,
            'printed_at' => now()->locale('id')->isoFormat('D MMMM YYYY HH:mm'),
        ])->setPaper('a4', 'portrait');

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $slip->employee?->name) ?: 'pegawai';
        $filename = 'Slip_Gaji_' . $safeName . '_' . $slip->period?->year . str_pad((string) $slip->period?->month, 2, '0', STR_PAD_LEFT) . '.pdf';

        return $pdf->stream($filename, ['Attachment' => false]);
    }
}

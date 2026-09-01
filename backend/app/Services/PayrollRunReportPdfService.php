<?php

namespace App\Services;

use App\Models\PayrollRun;
use App\Support\StandardLetterhead;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollRunReportPdfService
{
    public function __construct(protected PayrollRunExportService $exportService)
    {
    }

    public function stream(PayrollRun $run)
    {
        $report = $this->exportService->buildReport($run);
        $institution = $report['institution'];
        $letterheadHtml = StandardLetterhead::renderHtml($institution, true);

        $pdf = Pdf::loadView('payroll.run_report', [
            'institution' => $institution,
            'letterheadHtml' => $letterheadHtml,
            'run' => $report['run'],
            'period' => $report['run']->period,
            'slips' => $report['slips'],
            'totals' => $report['totals'],
            'printed_at' => now()->locale('id')->isoFormat('D MMMM YYYY HH:mm'),
        ])->setPaper('a4', 'landscape');

        $filename = $this->exportService->buildFilename($run, 'pdf');

        return $pdf->stream($filename, ['Attachment' => false]);
    }
}

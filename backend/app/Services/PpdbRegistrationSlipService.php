<?php

namespace App\Services;

use App\Models\PpdbApplicant;
use App\Support\PpdbDocumentChecklist;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PpdbRegistrationSlipService
{
    public function download(PpdbApplicant $applicant): Response
    {
        $applicant->loadMissing(['period.institution', 'period.academicYear', 'channel', 'documents']);
        $institution = $applicant->period?->institution;
        $checklist = PpdbDocumentChecklist::summarize($applicant->channel, $applicant->documents);
        $label = $institution?->resolvedAdmissionLabel() ?? 'PPDB';

        $pdf = Pdf::loadView('ppdb.registration_slip', [
            'applicant' => $applicant,
            'institution' => $institution,
            'checklist' => $checklist,
            'admission_label' => $label,
            'printed_at' => now()->locale('id')->isoFormat('D MMMM YYYY HH:mm'),
        ])->setPaper('a4', 'portrait');

        $filename = 'bukti-pendaftaran-'.preg_replace('/[^A-Za-z0-9\-]+/', '-', (string) $applicant->registration_number).'.pdf';

        return $pdf->download($filename);
    }
}

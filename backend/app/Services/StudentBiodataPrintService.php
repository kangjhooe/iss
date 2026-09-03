<?php

namespace App\Services;

use App\Models\Student;
use App\Support\PrintImage;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Response;

class StudentBiodataPrintService
{
    /**
     * Generate biodata PDF stream response.
     *
     * @param  'lengkap'|'singkat'  $mode
     */
    public function stream(Student $student, string $mode = 'lengkap', ?string $printedBy = null): Response
    {
        $mode = in_array($mode, ['lengkap', 'singkat'], true) ? $mode : 'lengkap';
        $asOf = now();

        $pdf = DomPDF::loadView('student.biodata_print', [
            'student' => $student,
            'institution' => $student->institution,
            'mode' => $mode,
            'photo_base64' => PrintImage::studentPhoto($student),
            'printed_at' => $asOf->locale('id')->isoFormat('D MMMM YYYY HH:mm'),
            'printed_by' => $printedBy,
            'signature_date' => $asOf->locale('id')->translatedFormat('d F Y'),
            'as_of_date' => $asOf,
        ])->setPaper('a4', 'portrait');

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $student->name ?? (string) $student->id);
        $filename = 'Biodata_'.($mode === 'singkat' ? 'Singkat_' : 'Lengkap_').$safeName.'.pdf';

        return $pdf->stream($filename, ['Attachment' => false]);
    }
}

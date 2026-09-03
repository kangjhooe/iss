<?php

namespace App\Services;

use App\Support\Guides\SiswaGuide;
use Symfony\Component\HttpFoundation\Response;

class SiswaGuidePdfService
{
    public function __construct(protected RoleGuidePdfService $pdfService)
    {
    }

    public function download(): Response
    {
        return $this->pdfService->download(SiswaGuide::definition(), 'siswa');
    }
}

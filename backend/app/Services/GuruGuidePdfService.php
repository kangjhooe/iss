<?php

namespace App\Services;

use App\Support\Guides\GuruGuide;
use Symfony\Component\HttpFoundation\Response;

class GuruGuidePdfService
{
    public function __construct(protected RoleGuidePdfService $pdfService)
    {
    }

    public function download(): Response
    {
        return $this->pdfService->download(GuruGuide::definition(), 'guru');
    }
}

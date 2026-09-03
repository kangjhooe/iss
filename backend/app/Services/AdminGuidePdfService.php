<?php

namespace App\Services;

use App\Support\Guides\AdminGuide;
use Symfony\Component\HttpFoundation\Response;

class AdminGuidePdfService
{
    public function __construct(protected RoleGuidePdfService $pdfService)
    {
    }

    public function download(): Response
    {
        return $this->pdfService->download(AdminGuide::definition(), 'admin');
    }
}

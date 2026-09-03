<?php

namespace App\Services;

use App\Support\Guides\OrangTuaGuide;
use Symfony\Component\HttpFoundation\Response;

class OrangTuaGuidePdfService
{
    public function __construct(protected RoleGuidePdfService $pdfService)
    {
    }

    public function download(): Response
    {
        return $this->pdfService->download(OrangTuaGuide::definition(), 'orang-tua');
    }
}

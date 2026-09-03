<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AdminGuidePdfService;
use App\Services\GuruGuidePdfService;
use App\Services\SiswaGuidePdfService;
use App\Services\OrangTuaGuidePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PublicGuideController extends Controller
{
    public function __construct(
        protected AdminGuidePdfService $adminGuidePdf,
        protected GuruGuidePdfService $guruGuidePdf,
        protected SiswaGuidePdfService $siswaGuidePdf,
        protected OrangTuaGuidePdfService $orangTuaGuidePdf,
    ) {
    }

    public function adminPdf(): Response|JsonResponse
    {
        return $this->pdfResponse(
            fn () => $this->adminGuidePdf->download(),
            'Failed to generate admin guide PDF',
            'Gagal membuat PDF panduan admin',
        );
    }

    public function guruPdf(): Response|JsonResponse
    {
        return $this->pdfResponse(
            fn () => $this->guruGuidePdf->download(),
            'Failed to generate guru guide PDF',
            'Gagal membuat PDF panduan guru',
        );
    }

    public function siswaPdf(): Response|JsonResponse
    {
        return $this->pdfResponse(
            fn () => $this->siswaGuidePdf->download(),
            'Failed to generate siswa guide PDF',
            'Gagal membuat PDF panduan siswa',
        );
    }

    public function orangTuaPdf(): Response|JsonResponse
    {
        return $this->pdfResponse(
            fn () => $this->orangTuaGuidePdf->download(),
            'Failed to generate orang-tua guide PDF',
            'Gagal membuat PDF panduan orang tua',
        );
    }

    private function pdfResponse(callable $generator, string $logMessage, string $userMessage): Response|JsonResponse
    {
        try {
            return $generator();
        } catch (\Throwable $e) {
            Log::error($logMessage, ['error' => $e->getMessage()]);

            return response()->json([
                'message' => $userMessage,
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}


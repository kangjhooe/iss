<?php

namespace App\Services;

use App\Support\PrintDocumentFooter;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class RoleGuidePdfService
{
    public function download(array $guide, string $slug): Response
    {
        $appName = PrintDocumentFooter::applicationName();
        $frontendUrl = rtrim((string) config('frontend.url', ''), '/');
        $guideUrl = $frontendUrl !== '' ? $frontendUrl.'/panduan/'.$slug : '/panduan/'.$slug;
        $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');

        $pdf = Pdf::loadView('guides.role', [
            'guide' => $guide,
            'appName' => $appName,
            'appTagline' => 'Sistem Informasi Sekolah & Madrasah',
            'guideUrl' => $guideUrl,
            'printed_at' => $printedAt,
            'closing_title' => $guide['closing_title'] ?? 'Siap mencoba?',
            'closing_desc' => $guide['closing_desc'] ?? 'Ikuti langkah di panduan ini secara berurutan.',
            'footer_label' => $guide['footer_label'] ?? $guide['title'],
        ])->setPaper('a4', 'portrait');

        $filename = 'panduan-'.$slug.'-'.strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $appName)).'.pdf';

        return $pdf->download($filename);
    }
}

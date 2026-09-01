<?php

namespace App\Services;

use App\Models\Correspondence;
use App\Repositories\CorrespondenceRepository;
use App\Support\StructuralPositionResolver;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;

class CorrespondenceExportService
{
    public function __construct(
        private CorrespondenceRepository $repository
    ) {}

    /**
     * Export correspondence to CSV.
     */
    public function exportToExcel(array $filters, ?int $institutionId = null): string
    {
        $correspondence = $this->repository->list($filters, $institutionId, 10000); // Get all matching records
        
        // Create CSV format (simpler, no need for Excel package)
        $fileName = 'correspondence_export_' . date('Y-m-d_His') . '.csv';
        $filePath = 'exports/' . $fileName;
        Storage::disk('public')->makeDirectory('exports');
        
        $file = fopen(Storage::disk('public')->path($filePath), 'w');
        
        // Add BOM for UTF-8
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($file, [
            'No',
            'Tipe',
            'Jenis Surat',
            'Nomor Surat',
            'Nomor Referensi',
            'Perihal',
            'Dari/Kepada',
            'Tanggal Surat',
            'Tanggal Terima',
            'Prioritas',
            'Status',
            'Kategori',
            'Pembuat',
            'Disetujui Oleh',
            'Tanggal Disetujui',
            'Keterangan',
        ]);
        
        // Data
        foreach ($correspondence->items() as $index => $item) {
            fputcsv($file, [
                $index + 1,
                ucfirst($item->type),
                $item->letter_type_name ?? '-',
                $item->letter_number ?? '-',
                $item->reference_number ?? '-',
                $item->subject,
                $item->type === 'masuk' ? ($item->from ?? '-') : ($item->to ?? '-'),
                $item->date?->format('Y-m-d'),
                $item->received_date?->format('Y-m-d'),
                ucfirst(str_replace('_', ' ', $item->priority)),
                ucfirst($item->status),
                $item->category?->name ?? '-',
                $item->creator?->name ?? '-',
                $item->approver?->name ?? '-',
                $item->approved_at?->format('Y-m-d H:i:s'),
                $item->description ?? '-',
            ]);
        }
        
        fclose($file);

        return $filePath;
    }

    /**
     * Export correspondence to PDF report.
     */
    public function exportToPdf(array $filters, ?int $institutionId = null, $institution = null): string
    {
        $correspondence = $this->repository->list($filters, $institutionId, 10000);
        
        $fileName = 'correspondence_report_' . date('Y-m-d_His') . '.pdf';
        $filePath = 'exports/' . $fileName;
        Storage::disk('public')->makeDirectory('exports');

        $generatedAt = now();
        $asOfDate = StructuralPositionResolver::reportAsOfDate($filters);

        $pdf = DomPDF::loadView('correspondence.report', [
            'correspondence' => $correspondence->items(),
            'filters' => $filters,
            'institution' => $institution,
            'generated_at' => $generatedAt,
            'as_of_date' => $asOfDate,
        ])->setPaper('a4', 'landscape');

        Storage::disk('public')->put($filePath, $pdf->output());

        return $filePath;
    }
}

<?php

namespace App\Services;

use App\Models\Correspondence;
use App\Repositories\CorrespondenceRepository;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;

class CorrespondenceExportService
{
    public function __construct(
        private CorrespondenceRepository $repository
    ) {}

    /**
     * Export correspondence to Excel.
     */
    public function exportToExcel(array $filters, ?int $institutionId = null): string
    {
        $correspondence = $this->repository->list($filters, $institutionId, 10000); // Get all matching records
        
        $export = new class($correspondence->items()) implements FromCollection, WithHeadings, WithMapping, WithStyles {
            private $items;

            public function __construct($items) {
                $this->items = $items;
            }

            public function collection() {
                return collect($this->items);
            }

            public function headings(): array {
                return [
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
                ];
            }

            public function map($correspondence): array {
                return [
                    $correspondence->id,
                    ucfirst($correspondence->type),
                    $correspondence->letter_type_name ?? '-',
                    $correspondence->letter_number ?? '-',
                    $correspondence->reference_number ?? '-',
                    $correspondence->subject,
                    $correspondence->type === 'masuk' ? ($correspondence->from ?? '-') : ($correspondence->to ?? '-'),
                    $correspondence->date?->format('Y-m-d'),
                    $correspondence->received_date?->format('Y-m-d'),
                    ucfirst(str_replace('_', ' ', $correspondence->priority)),
                    ucfirst($correspondence->status),
                    $correspondence->category?->name ?? '-',
                    $correspondence->creator?->name ?? '-',
                    $correspondence->approver?->name ?? '-',
                    $correspondence->approved_at?->format('Y-m-d H:i:s'),
                    $correspondence->description ?? '-',
                ];
            }

            public function styles(Worksheet $sheet) {
                return [
                    1 => ['font' => ['bold' => true]],
                ];
            }
        };

        $fileName = 'correspondence_export_' . date('Y-m-d_His') . '.xlsx';
        $filePath = 'exports/' . $fileName;
        
        Excel::store($export, $filePath, 'public');

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

        $pdf = DomPDF::loadView('correspondence.report', [
            'correspondence' => $correspondence->items(),
            'filters' => $filters,
            'institution' => $institution,
            'generated_at' => now(),
        ]);

        Storage::disk('public')->put($filePath, $pdf->output());

        return $filePath;
    }
}

<?php

namespace App\Services;

use App\Models\Correspondence;
use App\Models\CorrespondenceCategory;
use App\Repositories\CorrespondenceRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class CorrespondenceImportService
{
    public function __construct(
        private CorrespondenceRepository $repository
    ) {}

    /**
     * Import correspondence from Excel file.
     */
    public function importFromExcel(string $filePath, int $institutionId, int $userId): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $data = \Maatwebsite\Excel\Facades\Excel::toCollection(new class implements ToCollection, WithHeadingRow {
                public function collection(Collection $rows) {
                    return $rows;
                }
            }, $filePath)->first();

            DB::beginTransaction();

            foreach ($data as $index => $row) {
                try {
                    $rowNumber = $index + 2; // +2 because index starts at 0 and we skip header row

                    // Validate required fields
                    if (empty($row['tipe']) || empty($row['perihal']) || empty($row['tanggal_surat'])) {
                        $results['failed']++;
                        $results['errors'][] = "Baris {$rowNumber}: Tipe, Perihal, dan Tanggal Surat wajib diisi";
                        continue;
                    }

                    // Map Excel columns to database fields
                    $correspondenceData = [
                        'institution_id' => $institutionId,
                        'type' => strtolower($row['tipe'] ?? 'masuk'),
                        'subject' => $row['perihal'],
                        'date' => $this->parseDate($row['tanggal_surat']),
                        'priority' => $this->mapPriority($row['prioritas'] ?? 'biasa'),
                        'status' => $this->mapStatus($row['status'] ?? 'draft'),
                        'created_by' => $userId,
                    ];

                    // Optional fields
                    if (!empty($row['jenis_surat'])) {
                        $correspondenceData['letter_type_code'] = str_pad($row['jenis_surat'], 2, '0', STR_PAD_LEFT);
                    }

                    if (!empty($row['nomor_surat'])) {
                        $correspondenceData['letter_number'] = $row['nomor_surat'];
                    }

                    if (!empty($row['nomor_referensi'])) {
                        $correspondenceData['reference_number'] = $row['nomor_referensi'];
                    }

                    if (!empty($row['dari'])) {
                        $correspondenceData['from'] = $row['dari'];
                    }

                    if (!empty($row['kepada'])) {
                        $correspondenceData['to'] = $row['kepada'];
                    }

                    if (!empty($row['tanggal_terima'])) {
                        $correspondenceData['received_date'] = $this->parseDate($row['tanggal_terima']);
                    }

                    if (!empty($row['kategori'])) {
                        $category = CorrespondenceCategory::where('name', $row['kategori'])
                            ->where('institution_id', $institutionId)
                            ->first();
                        if ($category) {
                            $correspondenceData['category_id'] = $category->id;
                        }
                    }

                    if (!empty($row['keterangan'])) {
                        $correspondenceData['description'] = $row['keterangan'];
                    }

                    // Validate type
                    if (!in_array($correspondenceData['type'], ['masuk', 'keluar', 'internal'])) {
                        $results['failed']++;
                        $results['errors'][] = "Baris {$rowNumber}: Tipe surat harus 'masuk', 'keluar', atau 'internal'";
                        continue;
                    }

                    // Create correspondence
                    Correspondence::create($correspondenceData);
                    $results['success']++;

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Baris " . ($index + 2) . ": " . $e->getMessage();
                    Log::error('Failed to import correspondence row', [
                        'row' => $row->toArray(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Gagal mengimpor data: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Parse date from various formats.
     */
    private function parseDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        // Try to parse as Carbon date
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            // Try Excel date format (numeric)
            if (is_numeric($date)) {
                try {
                    return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
                } catch (\Exception $e2) {
                    return null;
                }
            }
            return null;
        }
    }

    /**
     * Map priority from Excel to database value.
     */
    private function mapPriority(string $priority): string
    {
        $priority = strtolower(trim($priority));
        $mapping = [
            'biasa' => 'biasa',
            'normal' => 'biasa',
            'penting' => 'penting',
            'important' => 'penting',
            'sangat penting' => 'sangat_penting',
            'sangat_penting' => 'sangat_penting',
            'very important' => 'sangat_penting',
        ];

        return $mapping[$priority] ?? 'biasa';
    }

    /**
     * Map status from Excel to database value.
     */
    private function mapStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $mapping = [
            'draft' => 'draft',
            'pending' => 'pending',
            'menunggu persetujuan' => 'pending',
            'approved' => 'approved',
            'disetujui' => 'approved',
            'sent' => 'sent',
            'terkirim' => 'sent',
            'archived' => 'archived',
            'diarsipkan' => 'archived',
        ];

        return $mapping[$status] ?? 'draft';
    }
}

<?php

namespace App\Services;

use App\Models\Correspondence;
use App\Models\CorrespondenceCategory;
use App\Repositories\CorrespondenceRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CorrespondenceImportService
{
    public function __construct(
        private CorrespondenceRepository $repository
    ) {}

    /**
     * Import correspondence from CSV file (template provided by API).
     */
    public function importFromExcel(string $filePath, int $institutionId, int $userId): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            // Read CSV file from public disk
            $fullPath = Storage::disk('public')->path($filePath);
            $file = fopen($fullPath, 'r');
            if ($file === false) {
                throw new \Exception('Gagal membuka file CSV untuk dibaca');
            }
            
            // Skip header row
            $rawHeaders = fgetcsv($file);
            if ($rawHeaders === false) {
                throw new \Exception('File CSV kosong atau tidak valid');
            }
            $headers = array_map([$this, 'normalizeHeader'], $rawHeaders);
            
            $data = [];
            while (($row = fgetcsv($file)) !== false) {
                if (count($row) < count($headers)) {
                    continue;
                }

                $row = array_slice($row, 0, count($headers));
                $combined = array_combine($headers, $row);
                if (is_array($combined)) {
                    $data[] = $combined;
                }
            }
            fclose($file);
            
            $data = collect($data);

            DB::beginTransaction();

            foreach ($data as $index => $row) {
                try {
                    $rowNumber = $index + 2; // +2 because index starts at 0 and we skip header row

                    // Validate required fields
                    $typeRaw = $this->clean($row['tipe'] ?? $row['type'] ?? null);
                    $subject = $this->clean($row['perihal'] ?? $row['subject'] ?? null);
                    $dateRaw = $this->clean($row['tanggal_surat'] ?? $row['date'] ?? null);

                    if (empty($typeRaw) || empty($subject) || empty($dateRaw)) {
                        $results['failed']++;
                        $results['errors'][] = "Baris {$rowNumber}: Tipe, Perihal, dan Tanggal Surat wajib diisi";
                        continue;
                    }

                    // Map Excel columns to database fields
                    $correspondenceData = [
                        'institution_id' => $institutionId,
                        'type' => strtolower($typeRaw),
                        'subject' => $subject,
                        'date' => $this->parseDate($dateRaw),
                        'priority' => $this->mapPriority($this->clean($row['prioritas'] ?? null) ?? 'biasa'),
                        'status' => $this->mapStatus($this->clean($row['status'] ?? null) ?? 'draft'),
                        'created_by' => $userId,
                    ];

                    // Optional fields
                    $letterType = $this->clean($row['jenis_surat'] ?? ($row['jenis_surat_01_16'] ?? null));
                    if (!empty($letterType)) {
                        $correspondenceData['letter_type_code'] = str_pad($letterType, 2, '0', STR_PAD_LEFT);
                    }

                    $letterNumber = $this->clean($row['nomor_surat'] ?? null);
                    if (!empty($letterNumber)) {
                        $correspondenceData['letter_number'] = $letterNumber;
                    }

                    $referenceNumber = $this->clean($row['nomor_referensi'] ?? null);
                    if (!empty($referenceNumber)) {
                        $correspondenceData['reference_number'] = $referenceNumber;
                    }

                    $from = $this->clean($row['dari'] ?? null);
                    if (!empty($from)) {
                        $correspondenceData['from'] = $from;
                    }

                    $to = $this->clean($row['kepada'] ?? null);
                    if (!empty($to)) {
                        $correspondenceData['to'] = $to;
                    }

                    $receivedDate = $this->clean($row['tanggal_terima'] ?? null);
                    if (!empty($receivedDate)) {
                        $correspondenceData['received_date'] = $this->parseDate($receivedDate);
                    }

                    $categoryName = $this->clean($row['kategori'] ?? null);
                    if (!empty($categoryName)) {
                        $category = CorrespondenceCategory::where('name', $categoryName)
                            ->where('institution_id', $institutionId)
                            ->first();
                        if ($category) {
                            $correspondenceData['category_id'] = $category->id;
                        }
                    }

                    $description = $this->clean($row['keterangan'] ?? null);
                    if (!empty($description)) {
                        $correspondenceData['description'] = $description;
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
                        'row' => $row,
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
     * Normalize CSV header to a predictable snake_case key.
     */
    private function normalizeHeader(?string $header): string
    {
        $header = (string) ($header ?? '');
        // Strip UTF-8 BOM if present
        $header = preg_replace('/^\xEF\xBB\xBF/u', '', $header) ?? $header;
        $header = strtolower(trim($header));
        $header = str_replace(['/', '-', '(', ')'], ' ', $header);
        $header = preg_replace('/\s+/', '_', $header) ?? $header;
        $header = preg_replace('/[^a-z0-9_]/', '', $header) ?? $header;
        $header = preg_replace('/_+/', '_', $header) ?? $header;
        return trim($header, '_');
    }

    /**
     * Clean string value from CSV (trim, turn empty into null).
     */
    private function clean($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    /**
     * Parse date from various formats.
     */
    private function parseDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        // Excel numeric date or unix timestamp often ends up in CSV as a number
        if (is_numeric($date)) {
            $n = (float) $date;

            // Excel serial date (days since 1899-12-30)
            if ($n > 20000 && $n < 80000) {
                $timestamp = (int) round(($n - 25569) * 86400);
                return Carbon::createFromTimestampUTC($timestamp)->format('Y-m-d');
            }

            // Unix timestamp (seconds)
            if ($n > 1000000000 && $n < 9999999999) {
                return Carbon::createFromTimestamp((int) $n)->format('Y-m-d');
            }
        }

        // Try to parse as Carbon date string
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
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

<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * Generate QR code untuk siswa atau guru.
     * Format QR: JSON dengan type, id, institution_id, timestamp
     */
    public function generateForStudent(int $studentId, int $institutionId): string
    {
        $data = [
            'type' => 'student',
            'id' => $studentId,
            'institution_id' => $institutionId,
            'timestamp' => now()->timestamp,
        ];

        return $this->generateQrCode(json_encode($data));
    }

    /**
     * Generate QR code untuk employee (guru/staff).
     */
    public function generateForEmployee(int $employeeId, int $institutionId): string
    {
        $data = [
            'type' => 'employee',
            'id' => $employeeId,
            'institution_id' => $institutionId,
            'timestamp' => now()->timestamp,
        ];

        return $this->generateQrCode(json_encode($data));
    }

    /**
     * Generate QR code image sebagai base64 string.
     */
    private function generateQrCode(string $data): string
    {
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($data);

        return 'data:image/png;base64,' . base64_encode($qrCode);
    }

    /**
     * Parse QR code data dari string.
     */
    public function parseQrData(string $qrData): ?array
    {
        $decoded = json_decode($qrData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if (!isset($decoded['type'], $decoded['id'], $decoded['institution_id'])) {
            return null;
        }

        return $decoded;
    }

    /**
     * Validasi QR code tidak expired (max 1 jam).
     */
    public function isValidTimestamp(int $timestamp): bool
    {
        $maxAge = 3600; // 1 jam dalam detik
        return (now()->timestamp - $timestamp) <= $maxAge;
    }
}

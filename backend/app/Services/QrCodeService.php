<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Student;
use App\Support\StudentRosterSort;
use Illuminate\Support\Collection;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public const TOKEN_PREFIX = 'ISS1';

    /**
     * Generate gambar QR untuk siswa (kartu tetap, ditandatangani).
     */
    public function generateForStudent(int $studentId, int $institutionId): string
    {
        return $this->generateQrCode($this->makeToken('student', $studentId, $institutionId));
    }

    /**
     * Generate gambar QR untuk employee (guru/staff).
     */
    public function generateForEmployee(int $employeeId, int $institutionId): string
    {
        return $this->generateQrCode($this->makeToken('employee', $employeeId, $institutionId));
    }

    public function makeToken(string $type, int $id, int $institutionId): string
    {
        $payload = json_encode([
            'v' => 1,
            't' => $type,
            'id' => $id,
            'iid' => $institutionId,
        ], JSON_UNESCAPED_SLASHES);

        $payloadB64 = $this->b64urlEncode($payload);
        $signature = $this->b64urlEncode(hash_hmac('sha256', $payloadB64, $this->secret(), true));

        return self::TOKEN_PREFIX.'.'.$payloadB64.'.'.$signature;
    }

    /**
     * Parse data QR. Hanya token tertanda yang diterima.
     *
     * @return array{type: string, id: int, institution_id: int}|null
     */
    public function parseQrData(string $qrData): ?array
    {
        $qrData = trim($qrData);
        if ($qrData === '' || !str_starts_with($qrData, self::TOKEN_PREFIX.'.')) {
            return null;
        }

        $parts = explode('.', $qrData);
        if (count($parts) !== 3) {
            return null;
        }

        [, $payloadB64, $signature] = $parts;
        if ($payloadB64 === '' || $signature === '') {
            return null;
        }

        $expected = $this->b64urlEncode(hash_hmac('sha256', $payloadB64, $this->secret(), true));
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $decoded = json_decode($this->b64urlDecode($payloadB64), true);
        if (!is_array($decoded) || (int) ($decoded['v'] ?? 0) !== 1) {
            return null;
        }

        $type = $decoded['t'] ?? null;
        if (!in_array($type, ['student', 'employee'], true)) {
            return null;
        }

        if (!isset($decoded['id'], $decoded['iid'])) {
            return null;
        }

        return [
            'type' => $type,
            'id' => (int) $decoded['id'],
            'institution_id' => (int) $decoded['iid'],
        ];
    }

    /**
     * Kartu QR siswa aktif. Wajib class_id dan/atau student_ids.
     *
     * @param  list<int>  $studentIds
     * @return Collection<int, array<string, mixed>>
     */
    public function studentCards(int $institutionId, ?int $classId, array $studentIds = [], int $limit = 300): Collection
    {
        $query = Student::query()
            ->with(['schoolClass:id,name,grade'])
            ->where('institution_id', $institutionId)
            ->active();

        if ($classId) {
            $query->where('class_id', $classId);
        }
        if ($studentIds !== []) {
            $query->whereIn('id', $studentIds);
        }

        $students = $query->limit($limit)->get()
            ->sort(fn (Student $a, Student $b) => StudentRosterSort::compareStudents($a, $b))
            ->values();

        return $students->map(function (Student $student) use ($institutionId) {
            $token = $this->makeToken('student', $student->id, $institutionId);

            return [
                'id' => $student->id,
                'name' => $student->name,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'class_name' => $student->schoolClass?->name,
                'qr_token' => $token,
                'qr_code' => $this->generateQrCode($token),
            ];
        });
    }

    /**
     * @param  list<int>  $employeeIds
     * @return Collection<int, array<string, mixed>>
     */
    public function employeeCards(int $institutionId, array $employeeIds = [], int $limit = 300): Collection
    {
        $query = Employee::query()
            ->forInstitution($institutionId)
            ->active()
            ->orderBy('type')
            ->orderBy('name');

        if ($employeeIds !== []) {
            $query->whereIn('id', $employeeIds);
        }

        return $query->limit($limit)->get()->map(function (Employee $employee) use ($institutionId) {
            $token = $this->makeToken('employee', $employee->id, $institutionId);

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'nip' => $employee->nip,
                'type' => $employee->type,
                'qr_token' => $token,
                'qr_code' => $this->generateQrCode($token),
            ];
        });
    }

    /**
     * Generate QR image sebagai data URI (SVG, tanpa dependensi Imagick).
     */
    public function generateQrCode(string $data): string
    {
        $svg = QrCode::format('svg')
            ->size(280)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($data);

        return 'data:image/svg+xml;base64,'.base64_encode((string) $svg);
    }

    private function secret(): string
    {
        return (string) config('app.key');
    }

    private function b64urlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }
}

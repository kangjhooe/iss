<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class StudentImportService
{
    public function __construct(
        protected StudentAccountService $studentAccountService
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $defaults
     * @return array{success_count:int,created_count:int,updated_count:int,error_count:int,errors:array<int,string>}
     */
    public function importFromRows(array $rows, Institution $institution, array $defaults = []): array
    {
        $successCount = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($rows as $index => $studentData) {
            $rowNumber = $this->rowNumber($studentData, $index);

            try {
                if (! is_array($studentData)) {
                    $errors[] = "Baris {$rowNumber}: format baris tidak valid";
                    $errorCount++;

                    continue;
                }

                $outcome = $this->importRow($studentData, $institution, $defaults);
                $successCount++;
                if ($outcome === 'created') {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }
            } catch (InvalidArgumentException $e) {
                $errors[] = "Baris {$rowNumber}: ".$e->getMessage();
                $errorCount++;
            } catch (Throwable $e) {
                $errors[] = "Baris {$rowNumber}: ".$this->friendlyError($e, is_array($studentData) ? $studentData : []);
                $errorCount++;
                Log::error('Failed to import student', [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                    'institution_id' => $institution->id,
                ]);
            }
        }

        return [
            'success_count' => $successCount,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'error_count' => $errorCount,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $studentData
     * @param  array<string, mixed>  $defaults
     */
    public function importRow(array $studentData, Institution $institution, array $defaults = []): string
    {
        $normalized = $this->normalizeRow($studentData);
        $payload = array_merge($normalized, $defaults, [
            'institution_id' => $institution->id,
        ]);

        if (empty($payload['semester_id']) && ! empty($defaults['semester_id'])) {
            $payload['semester_id'] = $defaults['semester_id'];
        }
        if (empty($payload['academic_year_id']) && ! empty($defaults['academic_year_id'])) {
            $payload['academic_year_id'] = $defaults['academic_year_id'];
        }

        $existing = $this->findExistingStudent(
            $institution->id,
            $payload['nik'] ?? null,
            $payload['nisn'] ?? null,
            $payload['nis'] ?? null
        );

        if ($existing) {
            $this->assertSameInstitution($existing, $institution, $payload);
            $this->assertStudentMayBeImported($existing);
            $previousNik = $existing->nik;
            $existing->update($this->payloadForUpdate($existing, $payload));
            $existing->refresh();
            $this->ensureAccountSafely($existing, $previousNik);

            return 'updated';
        }

        $created = Student::create($this->payloadForCreate($payload));
        $this->ensureAccountSafely($created);

        return 'created';
    }

    /**
     * @param  array<string, mixed>  $studentData
     * @return array<string, mixed>
     */
    public function normalizeRow(array $studentData): array
    {
        unset($studentData['excel_row'], $studentData['_excel_row']);

        $studentData['nik'] = $this->normalizeNik($studentData['nik'] ?? null);
        $studentData['nisn'] = $this->normalizeNisn($studentData['nisn'] ?? null);
        $studentData['nis'] = $this->nullableString($studentData['nis'] ?? null);

        foreach (['father_nik', 'mother_nik', 'guardian_nik', 'no_kk'] as $field) {
            if (array_key_exists($field, $studentData)) {
                $studentData[$field] = $this->digitsOnly($studentData[$field] ?? null);
            }
        }

        foreach ([
            'email', 'phone', 'class', 'name', 'birth_place',
            'address', 'village', 'sub_district', 'district', 'province', 'postal_code',
            'previous_school', 'previous_school_npsn', 'previous_school_address', 'notes',
            'father_name', 'father_birth_place', 'mother_name', 'mother_birth_place',
            'guardian_name', 'guardian_birth_place',
        ] as $field) {
            if (array_key_exists($field, $studentData)) {
                $studentData[$field] = $this->nullableString($studentData[$field]);
            }
        }

        if (isset($studentData['tingkat']) && $studentData['tingkat'] !== null && $studentData['tingkat'] !== '') {
            $studentData['tingkat'] = (int) $studentData['tingkat'];
        }

        if (empty($studentData['status'])) {
            $studentData['status'] = 'Aktif';
        }

        return $studentData;
    }

    public function normalizeNik(mixed $value): ?string
    {
        $digits = $this->digitsOnly($value);

        return $digits !== null && $digits !== '' ? $digits : null;
    }

    public function normalizeNisn(mixed $value): ?string
    {
        $digits = $this->digitsOnly($value);
        if ($digits === null || $digits === '') {
            return null;
        }

        if (strlen($digits) >= 8 && strlen($digits) <= 10) {
            return str_pad($digits, 10, '0', STR_PAD_LEFT);
        }

        return $digits;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function findExistingStudent(int $institutionId, ?string $nik, ?string $nisn, ?string $nis): ?Student
    {
        $byNik = $this->findByNik($nik, $institutionId);
        $byNisn = $this->findByNisn($nisn, $institutionId);
        $byNis = $this->findByNis($institutionId, $nis);

        if ($byNik && $byNisn && (int) $byNik->id !== (int) $byNisn->id) {
            throw new InvalidArgumentException(
                'NIK sudah dipakai '.$byNik->name.', sementara NISN '.$nisn.' sudah dipakai '.$byNisn->name
            );
        }

        if ($byNik && $byNis && (int) $byNik->id !== (int) $byNis->id) {
            throw new InvalidArgumentException(
                'NIK sudah dipakai '.$byNik->name.', sementara NIS '.$nis.' sudah dipakai '.$byNis->name
            );
        }

        if ($byNisn && $byNis && (int) $byNisn->id !== (int) $byNis->id) {
            throw new InvalidArgumentException(
                'NISN sudah dipakai '.$byNisn->name.', sementara NIS '.$nis.' sudah dipakai '.$byNis->name
            );
        }

        return $byNik ?? $byNisn ?? $byNis;
    }

    protected function findByNik(?string $nik, int $institutionId): ?Student
    {
        if ($nik === null || $nik === '') {
            return null;
        }

        return $this->findIdentityAtInstitutionOrBlocking('nik', $nik, $institutionId);
    }

    protected function findByNisn(?string $nisn, int $institutionId): ?Student
    {
        if ($nisn === null || $nisn === '') {
            return null;
        }

        $same = Student::withTrashed()
            ->where('institution_id', $institutionId)
            ->whereIn('nisn', $this->nisnVariants($nisn))
            ->first();
        if ($same) {
            return $same;
        }

        $others = Student::withTrashed()
            ->where('institution_id', '!=', $institutionId)
            ->whereIn('nisn', $this->nisnVariants($nisn))
            ->get();

        return $others->first(fn (Student $student) => $student->trashed() || $student->status !== 'Lulus');
    }

    protected function findIdentityAtInstitutionOrBlocking(string $column, string $value, int $institutionId): ?Student
    {
        $same = Student::withTrashed()
            ->where($column, $value)
            ->where('institution_id', $institutionId)
            ->first();
        if ($same) {
            return $same;
        }

        $others = Student::withTrashed()
            ->where($column, $value)
            ->where('institution_id', '!=', $institutionId)
            ->get();

        return $others->first(fn (Student $student) => $student->trashed() || $student->status !== 'Lulus');
    }

    protected function findByNis(int $institutionId, ?string $nis): ?Student
    {
        if ($nis === null || $nis === '') {
            return null;
        }

        return Student::withTrashed()
            ->where('institution_id', $institutionId)
            ->where('nis', $nis)
            ->first();
    }

    /**
     * @return array<int, string>
     */
    protected function nisnVariants(string $nisn): array
    {
        $trimmed = ltrim($nisn, '0');
        $variants = [$nisn];
        if ($trimmed !== '' && $trimmed !== $nisn) {
            $variants[] = $trimmed;
        }
        if (strlen($trimmed) >= 8 && strlen($trimmed) <= 10) {
            $variants[] = str_pad($trimmed, 10, '0', STR_PAD_LEFT);
        }

        return array_values(array_unique($variants));
    }

    /**
     * Siswa tidak aktif, alumni, mutasi, atau yang ada di kotak sampah tidak boleh diimpor ulang.
     */
    public function importBlockReason(Student $existing): ?string
    {
        $name = trim((string) ($existing->name ?: 'Siswa'));
        $nisn = $existing->nisn ? ' (NISN '.$existing->nisn.')' : '';
        $who = $name.$nisn;

        if ($existing->trashed()) {
            return "{$who} sudah dihapus (ada di kotak sampah). Tidak diimpor ulang. Pulihkan dulu dari Data Siswa jika siswa ini memang akan diaktifkan kembali.";
        }

        return match ((string) $existing->status) {
            '', 'Aktif' => null,
            'Pindah' => "{$who} berstatus Pindah (sudah dimutasi). Tidak diimpor ulang. Gunakan menu Mutasi jika siswa kembali, atau ubah status di Data Siswa.",
            'Tidak Aktif' => "{$who} berstatus Tidak Aktif. Tidak diimpor ulang. Aktifkan dulu di Data Siswa jika ini kesalahan.",
            'Drop Out' => "{$who} berstatus Drop Out. Tidak diimpor ulang. Ubah status di Data Siswa jika siswa ini kembali bersekolah.",
            'Lulus' => "{$who} berstatus Lulus (alumni). Tidak diimpor ulang. Jangan impor ulang data alumni.",
            default => "{$who} berstatus {$existing->status}. Siswa yang tidak aktif tidak boleh diimpor ulang.",
        };
    }

    protected function assertStudentMayBeImported(Student $existing): void
    {
        $reason = $this->importBlockReason($existing);
        if ($reason !== null) {
            throw new InvalidArgumentException($reason);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function assertSameInstitution(Student $existing, Institution $institution, array $payload): void
    {
        if ((int) $existing->institution_id === (int) $institution->id) {
            return;
        }

        $existing->loadMissing('institution');
        $other = $existing->institution?->name ?: 'institusi lain';
        $key = ($payload['nisn'] ?? null) && $existing->nisn === ($payload['nisn'] ?? null)
            ? 'NISN'
            : (($payload['nik'] ?? null) && $existing->nik === ($payload['nik'] ?? null) ? 'NIK' : 'Data siswa');

        throw new InvalidArgumentException(
            "{$key} sudah terdaftar di {$other}. Gunakan mutasi siswa, jangan impor ulang."
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function payloadForCreate(array $payload): array
    {
        foreach (['nik', 'nisn', 'nis', 'email', 'phone', 'class'] as $field) {
            if (($payload[$field] ?? null) === '') {
                $payload[$field] = null;
            }
        }

        return $payload;
    }

    /**
     * Jangan timpa NIK/NISN/NIS yang sudah ada dengan nilai kosong dari Excel.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function payloadForUpdate(Student $existing, array $payload): array
    {
        unset($payload['institution_id']);

        foreach (['nik', 'nisn', 'nis'] as $field) {
            if (($payload[$field] ?? null) === null || $payload[$field] === '') {
                unset($payload[$field]);
            }
        }

        foreach ($payload as $key => $value) {
            if ($value === null || $value === '') {
                unset($payload[$key]);
            }
        }

        if (
            array_key_exists('village', $payload)
            || array_key_exists('sub_district', $payload)
            || array_key_exists('district', $payload)
            || array_key_exists('province', $payload)
        ) {
            $payload['wilayah_province_code'] = null;
            $payload['wilayah_regency_code'] = null;
            $payload['wilayah_district_code'] = null;
            $payload['wilayah_village_code'] = null;
        }

        return $payload;
    }

    protected function ensureAccountSafely(Student $student, ?string $previousNik = null): void
    {
        try {
            $this->studentAccountService->ensureAccount($student, $previousNik);
        } catch (ValidationException $e) {
            Log::warning('Student import skipped account create', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $studentData
     */
    protected function rowNumber(mixed $studentData, int $index): int
    {
        if (is_array($studentData)) {
            $excelRow = $studentData['excel_row'] ?? $studentData['_excel_row'] ?? null;
            if (is_numeric($excelRow) && (int) $excelRow > 0) {
                return (int) $excelRow;
            }
        }

        return $index + 1;
    }

    /**
     * @param  array<string, mixed>  $studentData
     */
    protected function friendlyError(Throwable $e, array $studentData): string
    {
        if ($e instanceof UniqueConstraintViolationException || ($e instanceof QueryException && (string) $e->getCode() === '23000')) {
            $message = $e->getMessage();
            if (str_contains($message, 'nisn') || str_contains($message, 'student_nisn')) {
                $nisn = $this->normalizeNisn($studentData['nisn'] ?? null) ?: 'tersebut';

                return "NISN {$nisn} sudah terdaftar. Perbarui data siswa yang ada, atau cek apakah NISN bentrok dengan siswa lain.";
            }
            if (str_contains($message, 'nik') || str_contains($message, 'student_nik')) {
                $nik = $this->normalizeNik($studentData['nik'] ?? null) ?: 'tersebut';

                return "NIK {$nik} sudah terdaftar.";
            }
            if (str_contains($message, 'nis') || str_contains($message, 'student_nis')) {
                return 'NIS sudah terdaftar di sekolah ini.';
            }

            return 'Data bentrok dengan siswa yang sudah ada (NIK/NISN/NIS duplikat).';
        }

        return 'Gagal menyimpan data siswa';
    }

    protected function digitsOnly(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                return null;
            }

            return (string) (int) $value;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d+\.0+$/', $raw)) {
            $raw = preg_replace('/\.0+$/', '', $raw) ?? $raw;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        return $digits !== null && $digits !== '' ? $digits : null;
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}

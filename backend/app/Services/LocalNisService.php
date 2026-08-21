<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\InstitutionNisSequence;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use InvalidArgumentException;

class LocalNisService
{
    public const PRESET_TAHUN_URUT = 'tahun_urut';

    public const PRESET_TAHUN2_URUT = 'tahun2_urut';

    public const PRESET_URUT_SAJA = 'urut_saja';

    public const PRESET_PREFIX_TAHUN_URUT = 'prefix_tahun_urut';

    public const PRESET_PREFIX_TAHUN2_URUT = 'prefix_tahun2_urut';

    public const PRESET_NPSN4_TAHUN_URUT = 'npsn4_tahun_urut';

    public const PRESET_CUSTOM = 'custom';

    public const RESET_YEARLY = 'yearly';

    public const RESET_NEVER = 'never';

    public const PRESET_PATTERNS = [
        self::PRESET_TAHUN_URUT => '{YYYY}{SEQ}',
        self::PRESET_TAHUN2_URUT => '{YY}{SEQ}',
        self::PRESET_URUT_SAJA => '{SEQ}',
        self::PRESET_PREFIX_TAHUN_URUT => '{PREFIX}{YYYY}{SEQ}',
        self::PRESET_PREFIX_TAHUN2_URUT => '{PREFIX}{YY}{SEQ}',
        self::PRESET_NPSN4_TAHUN_URUT => '{NPSN4}{YY}{SEQ}',
        self::PRESET_CUSTOM => null,
    ];

    public function defaults(): array
    {
        return [
            'preset' => self::PRESET_TAHUN_URUT,
            'prefix' => '',
            'seq_digits' => 5,
            'reset' => self::RESET_YEARLY,
            'pattern' => '',
        ];
    }

    public function sequencesAvailable(): bool
    {
        return Schema::hasTable('institution_nis_sequences');
    }

    public function presetOptions(): array
    {
        return [
            [
                'value' => self::PRESET_TAHUN_URUT,
                'label' => 'Tahun + nomor urut',
                'example' => '202600001',
            ],
            [
                'value' => self::PRESET_TAHUN2_URUT,
                'label' => 'Tahun 2 digit + nomor urut',
                'example' => '2600001',
            ],
            [
                'value' => self::PRESET_URUT_SAJA,
                'label' => 'Nomor urut saja',
                'example' => '00001',
            ],
            [
                'value' => self::PRESET_PREFIX_TAHUN_URUT,
                'label' => 'Kode sekolah + tahun + nomor urut',
                'example' => 'S202600001',
            ],
            [
                'value' => self::PRESET_PREFIX_TAHUN2_URUT,
                'label' => 'Kode sekolah + tahun 2 digit + nomor urut',
                'example' => 'S2600001',
            ],
            [
                'value' => self::PRESET_NPSN4_TAHUN_URUT,
                'label' => '4 digit NPSN + tahun 2 digit + nomor urut',
                'example' => '707026001',
            ],
            [
                'value' => self::PRESET_CUSTOM,
                'label' => 'Pola kustom',
                'example' => '{PREFIX}/{YY}/{SEQ}',
            ],
        ];
    }

    public function normalize(?array $settings): array
    {
        $defaults = $this->defaults();
        $merged = array_merge($defaults, is_array($settings) ? $settings : []);

        $preset = (string) ($merged['preset'] ?? $defaults['preset']);
        if (! array_key_exists($preset, self::PRESET_PATTERNS)) {
            $preset = $defaults['preset'];
        }

        $seqDigits = (int) ($merged['seq_digits'] ?? $defaults['seq_digits']);
        $seqDigits = max(3, min(6, $seqDigits));

        $reset = (string) ($merged['reset'] ?? $defaults['reset']);
        if (! in_array($reset, [self::RESET_YEARLY, self::RESET_NEVER], true)) {
            $reset = $defaults['reset'];
        }

        return [
            'preset' => $preset,
            'prefix' => $this->sanitizePrefix((string) ($merged['prefix'] ?? '')),
            'seq_digits' => $seqDigits,
            'reset' => $reset,
            'pattern' => trim((string) ($merged['pattern'] ?? '')),
        ];
    }

    public function settingsFor(Institution $institution): array
    {
        return $this->normalize(is_array($institution->nis_numbering) ? $institution->nis_numbering : null);
    }

    public function missingNisCount(int $institutionId): int
    {
        return Student::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'Aktif')
            ->where(function ($q) {
                $q->whereNull('nis')->orWhere('nis', '');
            })
            ->count();
    }

    public function preview(Institution $institution, ?array $override = null): string
    {
        $settings = $this->normalize(array_merge($this->settingsFor($institution), $override ?? []));
        $yearCode = $this->yearCode($institution);
        $periodKey = $this->periodKey($settings, $yearCode);
        $nextSeq = $this->currentSeq($institution->id, $periodKey) + 1;

        return $this->render($institution, $settings, $yearCode, $nextSeq);
    }

    /**
     * Simulasi NIS untuk siswa tanpa nomor. Tidak menulis data dan tidak menaikkan counter.
     *
     * @param  array<int>|null  $studentIds
     * @return array{rows: array<int, array{id:int, name:?string, class:?string, tingkat:?int, proposed_nis:string}>, truncated: bool, total_missing: int}
     */
    public function previewAssignments(int $institutionId, ?array $studentIds = null, int $limit = 500): array
    {
        $institution = Institution::with('activeAcademicYear')->find($institutionId);
        if (! $institution) {
            throw new InvalidArgumentException('Institusi tidak ditemukan.');
        }

        $limit = max(1, min(2000, $limit));
        $aktifOnly = empty($studentIds);
        $totalMissing = $this->missingNisQuery($institutionId, $studentIds, $aktifOnly)->count();
        $students = $this->missingNisQuery($institutionId, $studentIds, $aktifOnly)
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'name', 'class', 'tingkat', 'nis', 'academic_year_id']);

        $proposed = $this->proposeForStudents($institution, $students);

        return [
            'rows' => $proposed['rows'],
            'truncated' => $totalMissing > $students->count(),
            'total_missing' => $totalMissing,
        ];
    }

    /**
     * Terapkan NIS hanya untuk siswa yang dipilih. Wajib student_ids agar tidak generate massal tanpa pratinjau.
     *
     * @param  array<int>  $studentIds
     * @return array{processed:int, assigned:int, skipped:int, errors:array<int, string>, assigned_rows:array<int, array{id:int, name:?string, class:?string, tingkat:?int, nis:string}>}
     */
    public function assignMany(int $institutionId, array $studentIds, int $limit = 500): array
    {
        $ids = array_values(array_unique(array_map('intval', $studentIds)));
        $ids = array_values(array_filter($ids, fn ($id) => $id > 0));
        if ($ids === []) {
            throw new InvalidArgumentException('Pilih minimal satu siswa untuk menerapkan NIS.');
        }

        $institution = Institution::with('activeAcademicYear')->find($institutionId);
        if (! $institution) {
            throw new InvalidArgumentException('Institusi tidak ditemukan.');
        }

        $settings = $this->settingsFor($institution);
        $this->assertSequencesAvailable();
        $this->assertCanGenerate($institution, $settings);

        $limit = max(1, min(2000, $limit));

        return DB::transaction(function () use ($institution, $settings, $ids, $limit) {
            $yearCode = $this->yearCode($institution);
            $periodKey = $this->periodKey($settings, $yearCode);
            $seqRow = InstitutionNisSequence::query()
                ->where('institution_id', $institution->id)
                ->where('period_key', $periodKey)
                ->lockForUpdate()
                ->first();

            if (! $seqRow) {
                $seqRow = InstitutionNisSequence::create([
                    'institution_id' => $institution->id,
                    'period_key' => $periodKey,
                    'last_seq' => 0,
                ]);
                $seqRow = InstitutionNisSequence::query()->whereKey($seqRow->id)->lockForUpdate()->firstOrFail();
            }

            $students = $this->missingNisQuery($institution->id, $ids, false)
                ->orderBy('id')
                ->limit($limit)
                ->lockForUpdate()
                ->get(['id', 'name', 'class', 'tingkat', 'nis', 'academic_year_id', 'institution_id']);

            $proposed = $this->proposeForStudents($institution, $students, (int) $seqRow->last_seq);
            $assignedRows = [];
            $errors = [];
            $skipped = count($ids) - $students->count();

            foreach ($proposed['rows'] as $row) {
                $updated = Student::query()
                    ->where('id', $row['id'])
                    ->where('institution_id', $institution->id)
                    ->where(function ($q) {
                        $q->whereNull('nis')->orWhere('nis', '');
                    })
                    ->update(['nis' => $row['proposed_nis']]);

                if ($updated) {
                    $assignedRows[] = [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'class' => $row['class'],
                        'tingkat' => $row['tingkat'],
                        'nis' => $row['proposed_nis'],
                    ];
                } else {
                    $skipped++;
                    $errors[] = ($row['name'] ?: 'ID '.$row['id']).': NIS sudah terisi atau siswa tidak ditemukan.';
                }
            }

            if ($proposed['last_seq'] > (int) $seqRow->last_seq) {
                $seqRow->last_seq = $proposed['last_seq'];
                $seqRow->save();
            }

            return [
                'processed' => $students->count(),
                'assigned' => count($assignedRows),
                'skipped' => max(0, $skipped),
                'errors' => $errors,
                'assigned_rows' => $assignedRows,
            ];
        });
    }

    /**
     * Reserve the next NIS for this institution (increments counter).
     */
    public function next(Institution $institution, ?int $academicYearId = null): string
    {
        $institution->loadMissing('activeAcademicYear');
        $settings = $this->settingsFor($institution);
        $this->assertSequencesAvailable();
        $this->assertCanGenerate($institution, $settings);

        $yearCode = $this->yearCode($institution, $academicYearId);
        $periodKey = $this->periodKey($settings, $yearCode);

        return DB::transaction(function () use ($institution, $settings, $yearCode, $periodKey) {
            $row = InstitutionNisSequence::query()
                ->where('institution_id', $institution->id)
                ->where('period_key', $periodKey)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $row = InstitutionNisSequence::create([
                    'institution_id' => $institution->id,
                    'period_key' => $periodKey,
                    'last_seq' => 0,
                ]);
                $row = InstitutionNisSequence::query()
                    ->whereKey($row->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $seq = (int) $row->last_seq;
            $attempts = 0;
            do {
                $seq++;
                $attempts++;
                $nis = $this->render($institution, $settings, $yearCode, $seq);
                $taken = Student::query()
                    ->where('institution_id', $institution->id)
                    ->where('nis', $nis)
                    ->exists();
            } while ($taken && $attempts < 200);

            if ($taken) {
                throw new InvalidArgumentException('Tidak dapat menghasilkan NIS unik. Periksa format atau nomor yang sudah terpakai.');
            }

            $row->last_seq = $seq;
            $row->save();

            return $nis;
        });
    }

    /**
     * Assign a generated NIS when the student does not have one yet.
     */
    public function assignIfEmpty(Student $student): ?string
    {
        if ($this->hasNis($student->nis)) {
            return null;
        }

        $institution = Institution::with('activeAcademicYear')->find($student->institution_id);
        if (! $institution) {
            throw new InvalidArgumentException('Institusi siswa tidak ditemukan.');
        }

        $nis = $this->next($institution, $student->academic_year_id ? (int) $student->academic_year_id : null);
        $student->nis = $nis;
        $student->save();

        return $nis;
    }

    public static function uniqueRule(?int $institutionId, mixed $ignoreStudentId = null): Unique
    {
        $rule = Rule::unique('student', 'nis')->where(function ($query) use ($institutionId) {
            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }
            $query->whereNotNull('nis')
                ->where('nis', '!=', '')
                ->whereNull('deleted_at');
        });

        if ($ignoreStudentId) {
            $rule->ignore($ignoreStudentId);
        }

        return $rule;
    }

    public function hasNis(mixed $nis): bool
    {
        return $nis !== null && trim((string) $nis) !== '';
    }

    public function yearCode(Institution $institution, ?int $academicYearId = null): string
    {
        $year = null;
        if ($academicYearId) {
            $year = AcademicYear::find($academicYearId);
        }
        if (! $year) {
            $institution->loadMissing('activeAcademicYear');
            $year = $institution->activeAcademicYear;
        }

        $raw = $year?->code ?: $year?->name ?: date('Y');
        $digits = preg_replace('/[^0-9]/', '', (string) $raw) ?: date('Y');

        return substr($digits, 0, 4) ?: date('Y');
    }

    private function periodKey(array $settings, string $yearCode): string
    {
        return $settings['reset'] === self::RESET_NEVER ? 'all' : $yearCode;
    }

    private function missingNisQuery(int $institutionId, ?array $studentIds, bool $aktifOnly)
    {
        $query = Student::query()
            ->where('institution_id', $institutionId)
            ->where(function ($q) {
                $q->whereNull('nis')->orWhere('nis', '');
            });

        if ($aktifOnly) {
            $query->where('status', 'Aktif');
        }

        if (! empty($studentIds)) {
            $query->whereIn('id', $studentIds);
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Student>  $students
     * @return array{rows: array<int, array{id:int, name:?string, class:?string, tingkat:?int, proposed_nis:string}>, last_seq: int}
     */
    private function proposeForStudents(Institution $institution, $students, ?int $startSeq = null): array
    {
        $settings = $this->settingsFor($institution);
        $this->assertCanGenerate($institution, $settings);
        $yearCode = $this->yearCode($institution);
        $seq = $startSeq !== null ? $startSeq : $this->currentSeq(
            $institution->id,
            $this->periodKey($settings, $yearCode)
        );

        $taken = Student::query()
            ->where('institution_id', $institution->id)
            ->whereNotNull('nis')
            ->where('nis', '!=', '')
            ->pluck('nis')
            ->flip()
            ->all();

        $rows = [];
        $lastSeq = $seq;

        foreach ($students as $student) {
            $attempts = 0;
            $nis = '';
            $exists = true;
            do {
                $seq++;
                $attempts++;
                $nis = $this->render($institution, $settings, $yearCode, $seq);
                $exists = isset($taken[$nis]);
            } while ($exists && $attempts < 200);

            if ($exists) {
                throw new InvalidArgumentException('Tidak dapat menghasilkan NIS unik. Periksa format atau nomor yang sudah terpakai.');
            }

            $taken[$nis] = true;
            $lastSeq = $seq;
            $rows[] = [
                'id' => (int) $student->id,
                'name' => $student->name,
                'class' => is_string($student->class) ? $student->class : ($student->class?->name ?? null),
                'tingkat' => $student->tingkat !== null ? (int) $student->tingkat : null,
                'proposed_nis' => $nis,
            ];
        }

        return [
            'rows' => $rows,
            'last_seq' => $lastSeq,
        ];
    }

    private function currentSeq(int $institutionId, string $periodKey): int
    {
        if (! $this->sequencesAvailable()) {
            return 0;
        }

        return (int) InstitutionNisSequence::query()
            ->where('institution_id', $institutionId)
            ->where('period_key', $periodKey)
            ->value('last_seq');
    }

    private function assertSequencesAvailable(): void
    {
        if (! $this->sequencesAvailable()) {
            throw new InvalidArgumentException(
                'Tabel penomoran NIS belum tersedia di database. Jalankan migrasi, lalu coba lagi.'
            );
        }
    }

    private function assertCanGenerate(Institution $institution, array $settings): void
    {
        if ($settings['preset'] === self::PRESET_CUSTOM) {
            $pattern = $settings['pattern'];
            if ($pattern === '' || ! preg_match('/\{SEQ(?::[1-8])?\}/', $pattern)) {
                throw new InvalidArgumentException('Pola kustom NIS harus berisi {SEQ} atau {SEQ:n}.');
            }
            $stripped = preg_replace('/\{(YYYY|YY|PREFIX|NPSN4|SEQ(?::[1-8])?)\}/', '', $pattern) ?? '';
            if (preg_match('/[^A-Za-z0-9\\/\\-._]/', $stripped)) {
                throw new InvalidArgumentException('Pola kustom NIS hanya boleh memakai token {YYYY}, {YY}, {PREFIX}, {NPSN4}, {SEQ} dan huruf, angka, /, -, _, atau titik.');
            }
        }

        if (in_array($settings['preset'], [self::PRESET_PREFIX_TAHUN_URUT, self::PRESET_PREFIX_TAHUN2_URUT], true)
            && $settings['prefix'] === '') {
            throw new InvalidArgumentException('Kode sekolah (prefix) wajib diisi untuk format NIS ini.');
        }

        if ($settings['preset'] === self::PRESET_NPSN4_TAHUN_URUT && ! $institution->npsn) {
            throw new InvalidArgumentException('NPSN institusi wajib diisi untuk format NIS ini.');
        }

        if ($settings['preset'] === self::PRESET_CUSTOM && str_contains($settings['pattern'], '{PREFIX}') && $settings['prefix'] === '') {
            throw new InvalidArgumentException('Kode sekolah (prefix) wajib diisi karena pola memakai {PREFIX}.');
        }
    }

    private function render(Institution $institution, array $settings, string $yearCode, int $seq): string
    {
        $pattern = $settings['preset'] === self::PRESET_CUSTOM
            ? $settings['pattern']
            : (self::PRESET_PATTERNS[$settings['preset']] ?? self::PRESET_PATTERNS[self::PRESET_TAHUN_URUT]);

        $digits = (int) $settings['seq_digits'];
        if (preg_match('/\{SEQ:([1-8])\}/', $pattern, $m)) {
            $digits = (int) $m[1];
        }

        $npsn4 = $institution->npsn
            ? str_pad(substr((string) $institution->npsn, -4), 4, '0', STR_PAD_LEFT)
            : '0000';

        $seqPadded = str_pad((string) $seq, $digits, '0', STR_PAD_LEFT);
        $nis = str_replace(
            ['{YYYY}', '{YY}', '{PREFIX}', '{NPSN4}', '{SEQ:3}', '{SEQ:4}', '{SEQ:5}', '{SEQ:6}', '{SEQ:7}', '{SEQ:8}', '{SEQ}'],
            [$yearCode, substr($yearCode, -2), $settings['prefix'], $npsn4, $seqPadded, $seqPadded, $seqPadded, $seqPadded, $seqPadded, $seqPadded, $seqPadded],
            $pattern
        );

        $nis = trim($nis);
        if ($nis === '' || strlen($nis) > 50) {
            throw new InvalidArgumentException('Hasil generate NIS tidak valid. Periksa format penomoran.');
        }

        return $nis;
    }

    private function sanitizePrefix(string $prefix): string
    {
        $clean = preg_replace('/[^A-Za-z0-9\\-\\/]/', '', trim($prefix)) ?? '';

        return substr($clean, 0, 12);
    }
}

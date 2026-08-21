<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\RegionAddress;
use App\Support\StudentIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class FeederAlumniEnrollmentService
{
    public function __construct(
        protected StudentService $studentService
    ) {}

    /**
     * @return array{origin: array{id:int,name:string,npsn:?string,level:?string}, data: list<array<string, mixed>>}
     */
    public function listAlumni(Institution $target, string $originNpsn, ?string $search = null, ?int $graduationYear = null): array
    {
        $origin = $this->resolveOrigin($target, $originNpsn);

        $query = Student::query()
            ->where('institution_id', $origin->id)
            ->where('status', 'Lulus')
            ->orderBy('name');

        if ($graduationYear) {
            $query->where('graduation_year', $graduationYear);
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('nik', 'like', $term)
                    ->orWhere('nisn', 'like', $term);
            });
        }

        $alumni = $query->limit(500)->get([
            'id', 'name', 'nisn', 'nik', 'gender', 'graduation_year',
        ]);

        $takenNisn = $this->takenKeys($target->id, 'nisn', $alumni->pluck('nisn')->all());
        $takenNik = $this->takenKeys($target->id, 'nik', $alumni->pluck('nik')->all());

        $data = $alumni->map(function (Student $student) use ($takenNisn, $takenNik) {
            $nisn = trim((string) ($student->nisn ?? ''));
            $nik = trim((string) ($student->nik ?? ''));
            $already = ($nisn !== '' && isset($takenNisn[$nisn]))
                || ($nik !== '' && isset($takenNik[$nik]));

            return [
                'id' => $student->id,
                'name' => $student->name,
                'nik' => $student->nik,
                'nisn' => $student->nisn,
                'gender' => $student->gender,
                'graduation_year' => $student->graduation_year,
                'already_enrolled' => $already,
            ];
        })->values()->all();

        return [
            'origin' => [
                'id' => $origin->id,
                'name' => $origin->name,
                'npsn' => $origin->npsn,
                'level' => $origin->level,
            ],
            'data' => $data,
        ];
    }

    /**
     * @param  array<int, int>  $studentIds
     * @return array{created: list<array<string, mixed>>, skipped: list<array{id:int,name:?string,reason:string}>, errors: list<array{id:int,name:?string,reason:string}>}
     */
    public function pull(Institution $target, string $originNpsn, array $studentIds, ?int $tingkat = null, ?int $classId = null): array
    {
        if (! $target->active_semester_id) {
            throw new InvalidArgumentException('Semester aktif belum ditetapkan untuk institusi ini.');
        }

        $origin = $this->resolveOrigin($target, $originNpsn);
        $ids = array_values(array_unique(array_map('intval', $studentIds)));
        if ($ids === []) {
            throw new InvalidArgumentException('Pilih minimal satu alumni.');
        }

        $tingkat = $tingkat ?: $target->defaultEntryGrade();
        $class = $this->resolveClass($target, $classId, $tingkat);

        $created = [];
        $skipped = [];
        $errors = [];

        $alumniRows = Student::query()
            ->where('institution_id', $origin->id)
            ->whereIn('id', $ids)
            ->get();

        $byId = $alumniRows->keyBy('id');

        foreach ($ids as $id) {
            $alumni = $byId->get($id);
            if (! $alumni) {
                $errors[] = ['id' => $id, 'name' => null, 'reason' => 'Alumni tidak ditemukan di sekolah asal.'];

                continue;
            }
            if ($alumni->status !== 'Lulus') {
                $skipped[] = ['id' => $id, 'name' => $alumni->name, 'reason' => 'Bukan alumni (status bukan Lulus).'];

                continue;
            }

            $reason = $this->alreadyEnrolledReason($target->id, $alumni);
            if ($reason) {
                $skipped[] = ['id' => $id, 'name' => $alumni->name, 'reason' => $reason];

                continue;
            }

            try {
                $student = DB::transaction(function () use ($alumni, $target, $origin, $tingkat, $class) {
                    return $this->studentService->create(
                        $this->payloadFromAlumni($alumni, $target, $origin, $tingkat, $class)
                    );
                });
                $created[] = [
                    'id' => $student->id,
                    'source_id' => $alumni->id,
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'nis' => $student->nis,
                ];
            } catch (\Throwable $e) {
                Log::warning('Gagal tarik alumni feeder', [
                    'alumni_id' => $alumni->id,
                    'target_institution_id' => $target->id,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = [
                    'id' => $id,
                    'name' => $alumni->name,
                    'reason' => $e->getMessage() ?: 'Gagal menyimpan siswa.',
                ];
            }
        }

        return compact('created', 'skipped', 'errors');
    }

    protected function resolveOrigin(Institution $target, string $originNpsn): Institution
    {
        $npsn = preg_replace('/\D/', '', $originNpsn) ?? '';
        if (strlen($npsn) !== 8) {
            throw new InvalidArgumentException('NPSN sekolah asal harus 8 digit.');
        }

        $origin = Institution::query()->where('npsn', $npsn)->where('is_active', true)->first();
        if (! $origin) {
            throw new InvalidArgumentException('Sekolah asal dengan NPSN tersebut tidak ditemukan atau tidak aktif.');
        }
        if ((int) $origin->id === (int) $target->id) {
            throw new InvalidArgumentException('Sekolah asal harus berbeda dengan sekolah Anda.');
        }
        if (! $target->canPullAlumniFrom($origin)) {
            throw new InvalidArgumentException(
                'Tarik alumni hanya dari jenjang sebelumnya (SD/MI → SMP/MTs, SMP/MTs → SMA/MA/SMK/MAK).'
            );
        }

        return $origin;
    }

    protected function resolveClass(Institution $target, ?int $classId, ?int $tingkat): ?SchoolClass
    {
        if (! $classId) {
            return null;
        }

        $class = SchoolClass::query()->whereKey($classId)->first();
        if (! $class || (int) $class->institution_id !== (int) $target->id) {
            throw new InvalidArgumentException('Kelas tidak berasal dari institusi yang dipilih.');
        }
        if ($tingkat !== null && $class->grade !== null && (int) $class->grade !== (int) $tingkat) {
            throw new InvalidArgumentException('Tingkat siswa harus sama dengan tingkat kelas.');
        }

        return $class;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<string, true>
     */
    protected function takenKeys(int $institutionId, string $column, array $values): array
    {
        $values = array_values(array_unique(array_filter(array_map(function ($value) {
            $value = trim((string) $value);

            return $value === '' ? null : $value;
        }, $values))));

        if ($values === []) {
            return [];
        }

        return Student::query()
            ->where('institution_id', $institutionId)
            ->whereIn($column, $values)
            ->pluck($column)
            ->mapWithKeys(fn ($value) => [(string) $value => true])
            ->all();
    }

    protected function alreadyEnrolledReason(int $targetInstitutionId, Student $alumni): ?string
    {
        $nisn = trim((string) ($alumni->nisn ?? ''));
        $nik = trim((string) ($alumni->nik ?? ''));

        if ($nisn !== '' && Student::query()->where('institution_id', $targetInstitutionId)->where('nisn', $nisn)->exists()) {
            return 'NISN sudah terdaftar di sekolah Anda.';
        }
        if ($nik !== '' && Student::query()->where('institution_id', $targetInstitutionId)->where('nik', $nik)->exists()) {
            return 'NIK sudah terdaftar di sekolah Anda.';
        }
        if ($nisn !== '' && StudentIdentity::isTakenByActive('nisn', $nisn)) {
            return 'NISN masih dipakai siswa aktif di sekolah lain.';
        }
        if ($nik !== '' && StudentIdentity::isTakenByActive('nik', $nik)) {
            return 'NIK masih dipakai siswa aktif di sekolah lain.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function payloadFromAlumni(
        Student $alumni,
        Institution $target,
        Institution $origin,
        ?int $tingkat,
        ?SchoolClass $class
    ): array {
        $data = $alumni->only([
            'nik',
            'nisn',
            'name',
            'gender',
            'birth_date',
            'birth_place',
            'phone',
            'email',
            'religion',
            'no_kk',
            'aspiration',
            'hobby',
            'disability',
            'height',
            'weight',
            'residence_type',
            'father_name',
            'father_status',
            'father_nik',
            'father_birth_place',
            'father_birth_date',
            'father_education',
            'father_occupation',
            'father_income',
            'mother_name',
            'mother_status',
            'mother_nik',
            'mother_birth_place',
            'mother_birth_date',
            'mother_education',
            'mother_occupation',
            'mother_income',
            'guardian_name',
            'guardian_phone',
            'guardian_type',
            'guardian_status',
            'guardian_nik',
            'guardian_birth_place',
            'guardian_birth_date',
            'guardian_education',
            'guardian_occupation',
            'guardian_income',
        ]);

        $data = array_merge($data, RegionAddress::values($alumni));
        $data['institution_id'] = $target->id;
        $data['nis'] = null;
        $data['status'] = 'Aktif';
        $data['graduation_year'] = null;
        $data['tingkat'] = $tingkat;
        $data['class_id'] = $class?->id;
        $data['class'] = $class?->name;
        $data['semester_id'] = $target->active_semester_id;
        $data['academic_year_id'] = $class?->academic_year_id ?: $target->active_academic_year_id;
        $data['previous_school'] = $alumni->previous_school ?: $origin->name;
        $data['previous_school_npsn'] = $alumni->previous_school_npsn ?: $origin->npsn;
        $data['previous_school_address'] = $alumni->previous_school_address;

        foreach (['birth_date', 'father_birth_date', 'mother_birth_date', 'guardian_birth_date'] as $dateField) {
            if (! empty($data[$dateField]) && ! is_string($data[$dateField])) {
                $data[$dateField] = $alumni->{$dateField}?->format('Y-m-d');
            }
        }

        return $data;
    }
}

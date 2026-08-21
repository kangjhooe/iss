<?php

namespace App\Services;

use App\Models\ClassStudentHistory;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class StudentService
{
    public function __construct(
        protected StudentAccountService $studentAccountService,
        protected LocalNisService $localNisService = new LocalNisService
    ) {}

    /** Sentinel values for "tanpa kelas / tanpa tingkat" filters. */
    public const UNASSIGNED_VALUES = ['__none__', 'unassigned', 'none'];

    private const ALLOWED_SORTS = [
        'name',
        'nik',
        'nis',
        'nisn',
        'gender',
        'tingkat',
        'class',
        'status',
        'created_at',
    ];

    public static function isUnassignedFilter(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return in_array(strtolower((string) $value), self::UNASSIGNED_VALUES, true);
    }

    /**
     * Get list of students with filters.
     */
    public function list(array $filters, ?int $institutionId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildListQuery($filters, $institutionId);

        $perPage = min($perPage, 100); // Max 100 per page

        // Include graduation_year for list
        return $query->select(['id', 'institution_id', 'nik', 'nis', 'nisn', 'name', 'gender', 'birth_date', 'tingkat', 'class', 'class_id', 'academic_year', 'academic_year_id', 'semester_id', 'status', 'graduation_year', 'created_at'])
            ->with([
                'institution:id,name,npsn',
                'class:id,name,grade,academic_year_id',
                'academicYear:id,name,code',
                'semester:id,name,academic_year_id',
                'userAccount:id,name,email,login_nik,must_change_password,is_active,role',
            ])
            ->paginate($perPage);
    }

    /**
     * Full student rows for Excel export (no pagination, full columns).
     */
    public function listForExport(array $filters, ?int $institutionId = null, int $limit = 20000): Collection
    {
        $query = $this->buildListQuery($filters, $institutionId);

        return $query
            ->with([
                'institution:id,name,npsn',
                'class:id,name,grade,academic_year_id',
                'academicYear:id,name,code',
                'semester:id,name,academic_year_id',
            ])
            ->limit(max(1, min($limit, 50000)))
            ->get();
    }

    protected function buildListQuery(array $filters, ?int $institutionId = null): Builder
    {
        $query = Student::query();

        if (! empty($filters['only_trashed'])) {
            $query->onlyTrashed();
        } elseif (! empty($filters['with_trashed'])) {
            $query->withTrashed();
        }

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $this->applyListFilters($query, $filters);
        $this->applyListSorting($query, $filters);

        return $query;
    }

    protected function applyListFilters(Builder $query, array $filters): void
    {
        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('nik', 'like', '%'.$search.'%')
                    ->orWhere('nis', 'like', '%'.$search.'%')
                    ->orWhere('nisn', 'like', '%'.$search.'%');
            });
        }

        if (isset($filters['class']) && $filters['class'] !== '' && ! self::isUnassignedFilter($filters['class'])) {
            $query->where('class', $filters['class']);
        }

        if (array_key_exists('class_id', $filters) && $filters['class_id'] !== '' && $filters['class_id'] !== null) {
            if (self::isUnassignedFilter($filters['class_id'])) {
                $query->withoutAssignedClass();
            } else {
                $query->where('class_id', $filters['class_id']);
            }
        }

        if (! empty($filters['class_ids']) && is_array($filters['class_ids'])) {
            $query->whereIn('class_id', $filters['class_ids']);
        }

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (isset($filters['semester_id']) && $filters['semester_id'] !== '' && $filters['semester_id'] !== null) {
            $query->where('semester_id', $filters['semester_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['gender']) && $filters['gender'] !== '') {
            $query->where('gender', $filters['gender']);
        }

        if (array_key_exists('tingkat', $filters) && $filters['tingkat'] !== '' && $filters['tingkat'] !== null) {
            if (self::isUnassignedFilter($filters['tingkat'])) {
                $query->whereNull('tingkat');
            } else {
                $query->where('tingkat', $filters['tingkat']);
            }
        }

        $this->applyAccountStatusFilter($query, $filters['account_status'] ?? null);

        if (! empty($filters['missing_nis']) && filter_var($filters['missing_nis'], FILTER_VALIDATE_BOOLEAN)) {
            $query->missingNis();
        }
    }

    /**
     * Filter by login account readiness.
     * - ready: punya akun role=student
     * - missing: NIK+tgl lahir lengkap tapi belum punya akun
     * - incomplete: NIK/tgl lahir belum valid
     */
    protected function applyAccountStatusFilter(Builder $query, mixed $accountStatus): void
    {
        $status = is_string($accountStatus) ? strtolower(trim($accountStatus)) : '';
        if ($status === '' || $status === 'all') {
            return;
        }

        $hasAccount = function ($q) {
            $q->select(DB::raw(1))
                ->from('user')
                ->whereColumn('user.login_nik', 'student.nik')
                ->where('user.role', 'student');
        };

        $eligibleNikBirth = function ($q) {
            $q->whereNotNull('nik')
                ->where('nik', '!=', '')
                ->whereRaw("TRIM(nik) REGEXP '^[0-9]{16}$'")
                ->whereNotNull('birth_date');
        };

        if ($status === 'ready' || $status === 'with_account') {
            $query->whereExists($hasAccount);

            return;
        }

        if ($status === 'missing' || $status === 'without_account') {
            $query->where($eligibleNikBirth)->whereNotExists($hasAccount);

            return;
        }

        if ($status === 'incomplete' || $status === 'incomplete_data') {
            $query->where(function ($q) {
                $q->whereNull('nik')
                    ->orWhere('nik', '')
                    ->orWhereRaw("TRIM(nik) NOT REGEXP '^[0-9]{16}$'")
                    ->orWhereNull('birth_date');
            });
        }
    }

    /**
     * Ringkasan kesiapan akun login untuk filter daftar saat ini (tanpa account_status).
     *
     * @return array{total: int, with_account: int, missing_account: int, incomplete_data: int}
     */
    public function accountStatusSummary(array $filters, ?int $institutionId = null): array
    {
        unset($filters['account_status']);
        $base = $this->buildListQuery($filters, $institutionId);

        $hasAccount = function ($q) {
            $q->select(DB::raw(1))
                ->from('user')
                ->whereColumn('user.login_nik', 'student.nik')
                ->where('user.role', 'student');
        };

        $total = (clone $base)->count();
        $withAccount = (clone $base)->whereExists($hasAccount)->count();
        $missingAccount = (clone $base)
            ->whereNotNull('nik')
            ->where('nik', '!=', '')
            ->whereRaw("TRIM(nik) REGEXP '^[0-9]{16}$'")
            ->whereNotNull('birth_date')
            ->whereNotExists($hasAccount)
            ->count();
        $incompleteData = (clone $base)
            ->where(function ($q) {
                $q->whereNull('nik')
                    ->orWhere('nik', '')
                    ->orWhereRaw("TRIM(nik) NOT REGEXP '^[0-9]{16}$'")
                    ->orWhereNull('birth_date');
            })
            ->count();

        return [
            'total' => $total,
            'with_account' => $withAccount,
            'missing_account' => $missingAccount,
            'incomplete_data' => $incompleteData,
        ];
    }

    /**
     * Buat akun login massal untuk siswa yang cocok filter / id tertentu.
     *
     * @param  list<int>|null  $studentIds
     * @return array{
     *     processed: int,
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     errors: list<array{student_id: int|null, name: string|null, reason: string}>
     * }
     */
    public function bulkEnsureAccounts(
        array $filters,
        ?int $institutionId = null,
        ?array $studentIds = null,
        bool $onlyMissing = true,
        int $limit = 500
    ): array {
        if ($onlyMissing && empty($filters['account_status'])) {
            $filters['account_status'] = 'missing';
        }

        $query = $this->buildListQuery($filters, $institutionId)
            ->select(['id', 'institution_id', 'nik', 'name', 'email', 'birth_date']);

        if (! empty($studentIds)) {
            $ids = array_values(array_unique(array_map('intval', $studentIds)));
            $query->whereIn('id', $ids);
        }

        $limit = max(1, min($limit, 2000));
        $students = $query->limit($limit)->get();

        return $this->studentAccountService->bulkEnsure($students);
    }

    protected function applyListSorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sortBy, self::ALLOWED_SORTS, true)) {
            $sortBy = 'created_at';
        }

        // Keep nulls at the end so "tanpa data" tidak mengacaukan urutan A–Z.
        if (in_array($sortBy, ['name', 'nik', 'nis', 'nisn', 'tingkat', 'class', 'status', 'gender'], true)) {
            $query->orderByRaw("CASE WHEN `{$sortBy}` IS NULL OR `{$sortBy}` = '' THEN 1 ELSE 0 END");
        }

        $query->orderBy($sortBy, $sortDir)->orderBy('id', 'asc');
    }

    /**
     * Keep student.class in sync with the linked class name when class_id is set.
     */
    protected function syncClassLabel(array $data): array
    {
        if (! array_key_exists('class_id', $data)) {
            return $data;
        }

        $classId = $data['class_id'] ? (int) $data['class_id'] : null;
        if (! $classId) {
            $data['class_id'] = null;
            if (! array_key_exists('class', $data) || $data['class'] === '' || $data['class'] === null) {
                $data['class'] = null;
            }

            return $data;
        }

        $name = \App\Models\SchoolClass::whereKey($classId)->value('name');
        if (is_string($name) && trim($name) !== '') {
            $data['class'] = $name;
        }

        return $data;
    }

    /**
     * Keep student.academic_year in short code form (e.g. 2025/2026), not full name.
     */
    protected function syncAcademicYearLabel(array $data): array
    {
        $academicYearId = $data['academic_year_id'] ?? null;
        if ($academicYearId) {
            $code = \App\Models\AcademicYear::whereKey($academicYearId)->value('code');
            if ($code) {
                $data['academic_year'] = $code;

                return $data;
            }
        }

        if (! empty($data['academic_year']) && is_string($data['academic_year'])) {
            if (preg_match('/(\d{4}\/\d{4})/', $data['academic_year'], $matches)) {
                $data['academic_year'] = $matches[1];
            }
        }

        return $data;
    }

    /**
     * Create a new student.
     */
    public function create(array $data): Student
    {
        $data = $this->syncClassLabel($this->syncAcademicYearLabel($data));

        if (! $this->localNisService->hasNis($data['nis'] ?? null) && ! empty($data['institution_id'])) {
            $institution = Institution::with('activeAcademicYear')->find($data['institution_id']);
            if ($institution && $this->localNisService->sequencesAvailable()) {
                try {
                    $data['nis'] = $this->localNisService->next(
                        $institution,
                        isset($data['academic_year_id']) ? (int) $data['academic_year_id'] : null
                    );
                } catch (InvalidArgumentException $e) {
                    throw $e;
                }
            }
        }

        $student = Student::create($data);

        // Create initial class history if class_id is provided
        if (isset($data['class_id']) && isset($data['academic_year_id'])) {
            $semesterId = $data['semester_id'] ?? null;
            $this->createClassHistory($student, $data['class_id'], $data['academic_year_id'], $semesterId, 'masuk');
        }

        $this->studentAccountService->ensureAccount($student);

        Log::info('Student created', [
            'student_id' => $student->id,
            'institution_id' => $student->institution_id,
        ]);

        return $student->load(['institution', 'class', 'academicYear', 'semester', 'userAccount']);
    }

    /**
     * Get student by ID with relationships.
     */
    public function find(int $id, array $with = ['institution', 'documents', 'class', 'academicYear', 'semester', 'classHistory']): Student
    {
        return Student::with($with)->findOrFail($id);
    }

    /**
     * Update student with automatic history tracking.
     */
    public function update(Student $student, array $data): Student
    {
        // Track changes for history BEFORE update
        $oldClassId = $student->class_id;
        $oldAcademicYearId = $student->academic_year_id;
        $oldStatus = $student->status;
        $previousNik = $student->nik;

        $data = $this->syncClassLabel($this->syncAcademicYearLabel($data));

        // Update student
        $student->update($data);

        // Refresh to get updated values
        $student->refresh();

        // Get new values AFTER update
        $newClassId = $student->class_id;
        $newAcademicYearId = $student->academic_year_id;
        $newStatus = $student->status;

        // Determine if we need to create history
        $classChanged = $oldClassId != $newClassId;
        $academicYearChanged = $oldAcademicYearId != $newAcademicYearId;
        $statusChanged = $oldStatus != $newStatus;

        // Handle history creation
        if ($classChanged || $academicYearChanged || $statusChanged) {
            // End previous active history record if exists
            if ($oldClassId && $oldAcademicYearId) {
                $this->endPreviousHistory($student->id, $oldClassId, $oldAcademicYearId);
            }

            // Create new history record if new class/year is set
            if ($newClassId && $newAcademicYearId) {
                $historyStatus = $this->determineHistoryStatus($classChanged, $academicYearChanged, $statusChanged, $newStatus);
                $semesterId = $student->semester_id ?? $data['semester_id'] ?? null;
                $this->createClassHistory($student, $newClassId, $newAcademicYearId, $semesterId, $historyStatus);
            }
        }

        $this->studentAccountService->ensureAccount($student, $previousNik);

        Log::info('Student updated', [
            'student_id' => $student->id,
            'class_changed' => $classChanged,
            'academic_year_changed' => $academicYearChanged,
            'status_changed' => $statusChanged,
        ]);

        return $student->fresh(['institution', 'class', 'academicYear', 'semester', 'documents', 'userAccount']);
    }

    /**
     * Determine history status based on changes.
     * Nilai harus cocok dengan enum class_student_history.status:
     * Aktif | Pindah | Lulus | Drop Out
     */
    protected function determineHistoryStatus(bool $classChanged, bool $academicYearChanged, bool $statusChanged, string $newStatus): string
    {
        if ($statusChanged && in_array($newStatus, ['Lulus', 'Pindah', 'Drop Out'], true)) {
            return $newStatus;
        }

        if ($academicYearChanged) {
            return 'Aktif'; // naik kelas ke tahun ajaran baru
        }

        if ($classChanged) {
            return 'Pindah';
        }

        return 'Aktif';
    }

    /**
     * Normalisasi status riwayat kelas ke nilai enum database.
     */
    protected function normalizeHistoryStatus(string $status): string
    {
        $map = [
            'masuk' => 'Aktif',
            'naik_kelas' => 'Aktif',
            'update' => 'Aktif',
            'aktif' => 'Aktif',
            'Aktif' => 'Aktif',
            'pindah' => 'Pindah',
            'Pindah' => 'Pindah',
            'lulus' => 'Lulus',
            'Lulus' => 'Lulus',
            'drop_out' => 'Drop Out',
            'Drop Out' => 'Drop Out',
        ];

        return $map[$status] ?? 'Aktif';
    }

    /**
     * Delete student (soft delete).
     */
    public function delete(Student $student): bool
    {
        $studentId = $student->id;
        $result = $student->delete();

        Log::info('Student deleted', [
            'student_id' => $studentId,
        ]);

        return $result;
    }

    /**
     * Permanently delete a student that is already in the trash.
     */
    public function forceDelete(Student $student): void
    {
        if (! $student->trashed()) {
            throw new InvalidArgumentException('Hanya data di kotak sampah yang dapat dihapus permanen.');
        }

        $studentId = $student->id;
        $nisn = $student->nisn;
        $nik = $student->nik;

        DB::transaction(function () use ($student) {
            $this->studentAccountService->deleteLoginAccount($student);

            foreach ($student->documents()->get(['id', 'file_path']) as $document) {
                if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }
            }
            Storage::disk('public')->deleteDirectory('student_documents/'.$student->id);

            $student->forceDelete();
        });

        Log::info('Student permanently deleted', [
            'student_id' => $studentId,
            'nisn' => $nisn,
            'nik' => $nik,
        ]);
    }

    /**
     * Create class history record.
     */
    protected function createClassHistory(Student $student, ?int $classId, ?int $academicYearId, ?int $semesterId = null, string $status = 'masuk'): ?ClassStudentHistory
    {
        if (! $classId || ! $academicYearId) {
            return null;
        }

        // Kolom student.class adalah string nama kelas — jangan dipakai sebagai relasi.
        $class = \App\Models\SchoolClass::find($classId);
        $academicYear = $student->relationLoaded('academicYear') && (int) $student->academic_year_id === (int) $academicYearId
            ? $student->academicYear
            : \App\Models\AcademicYear::find($academicYearId);

        if (! $class || ! $academicYear) {
            Log::warning('Cannot create class history - class or academic year not found', [
                'student_id' => $student->id,
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
            ]);

            return null;
        }

        // Use semester_id from student if not provided
        if (! $semesterId) {
            $semesterId = $student->semester_id;
        }

        return ClassStudentHistory::create([
            'student_id' => $student->id,
            'class_id' => $classId,
            'academic_year' => $academicYear->name ?? $student->academic_year,
            'academic_year_id' => $academicYearId,
            'semester_id' => $semesterId,
            'start_date' => now(),
            'status' => $this->normalizeHistoryStatus($status),
            'notes' => "Auto-generated: {$status}",
        ]);
    }

    /**
     * End previous history record.
     */
    protected function endPreviousHistory(int $studentId, ?int $classId, ?int $academicYearId): void
    {
        if (! $classId || ! $academicYearId) {
            return;
        }

        $updated = ClassStudentHistory::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->whereNull('end_date')
            ->update([
                'end_date' => now(),
            ]);

        if ($updated > 0) {
            Log::info('Ended previous class history', [
                'student_id' => $studentId,
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
            ]);
        }
    }

    /**
     * List alumni (siswa dengan status Lulus) dengan filter.
     */
    public function listAlumni(array $filters, ?int $institutionId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Student::query()->alumni();

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('nisn', 'like', '%'.$search.'%')
                    ->orWhere('nis', 'like', '%'.$search.'%')
                    ->orWhere('nik', 'like', '%'.$search.'%');
            });
        }

        if (! empty($filters['graduation_year'])) {
            $query->byGraduationYear((int) $filters['graduation_year']);
        }

        if (! empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        $perPage = min($perPage, 100);

        return $query
            ->select(['id', 'institution_id', 'nik', 'nis', 'nisn', 'name', 'gender', 'tingkat', 'class', 'class_id', 'academic_year', 'academic_year_id', 'graduation_year', 'status', 'created_at'])
            ->with([
                'institution:id,name,npsn',
                'class:id,name,grade',
                'academicYear:id,name,code',
                'currentAlumniDestination',
            ])
            ->orderBy('graduation_year', 'desc')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Luluskan satu siswa (set status Lulus, tutup riwayat kelas, isi tahun lulus).
     */
    public function graduateSingle(Student $student, ?int $graduationYear = null): Student
    {
        if ($student->status === 'Lulus') {
            throw new \InvalidArgumentException('Siswa sudah berstatus Lulus.');
        }

        if ($student->status !== 'Aktif') {
            throw new \InvalidArgumentException('Hanya siswa aktif yang dapat diluluskan.');
        }

        $year = $graduationYear ?? $this->inferGraduationYearFromStudent($student);

        $oldClassId = $student->class_id;
        $oldAcademicYearId = $student->academic_year_id;
        $oldSemesterId = $student->semester_id;

        // Samakan tahun ajaran/semester dengan kelas jika data siswa tidak sinkron
        // (sering terjadi setelah naik tahun ajaran tanpa pindah class_id).
        // Catatan: $student->class adalah kolom string nama kelas, bukan relasi.
        if ($oldClassId) {
            $classModel = $student->relationLoaded('class')
                ? $student->getRelation('class')
                : \App\Models\SchoolClass::find($oldClassId);
            if ($classModel instanceof \App\Models\SchoolClass) {
                if ($classModel->academic_year_id && (int) $oldAcademicYearId !== (int) $classModel->academic_year_id) {
                    $oldAcademicYearId = (int) $classModel->academic_year_id;
                }
                if ($classModel->semester_id && (int) $oldSemesterId !== (int) $classModel->semester_id) {
                    $oldSemesterId = (int) $classModel->semester_id;
                }
            }
        }

        if ($oldClassId && $oldAcademicYearId) {
            $this->endPreviousHistory($student->id, $oldClassId, $oldAcademicYearId);
            $this->createClassHistory($student, $oldClassId, $oldAcademicYearId, $oldSemesterId, 'Lulus');
        }

        $student->update([
            'status' => 'Lulus',
            'graduation_year' => $year,
            'academic_year_id' => $oldAcademicYearId ?: $student->academic_year_id,
            'semester_id' => $oldSemesterId ?: $student->semester_id,
        ]);

        Log::info('Student graduated', [
            'student_id' => $student->id,
            'graduation_year' => $year,
        ]);

        return $student->fresh(['institution', 'class', 'academicYear', 'semester']);
    }

    /**
     * Luluskan banyak siswa sekaligus.
     *
     * @param  array<int>  $studentIds
     * @return array{success: int, failed: array<array{id: int, reason: string}>}
     */
    public function graduateBulk(array $studentIds, ?int $graduationYear = null): array
    {
        $year = $graduationYear ?? (int) date('Y');
        $success = 0;
        $failed = [];

        foreach ($studentIds as $id) {
            $student = Student::find($id);
            if (! $student) {
                $failed[] = ['id' => $id, 'reason' => 'Siswa tidak ditemukan.'];

                continue;
            }
            try {
                $this->graduateSingle($student, $year);
                $success++;
            } catch (\Throwable $e) {
                $failed[] = ['id' => $id, 'reason' => $e->getMessage()];
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Batalkan kelulusan: kembalikan status Aktif dan pulihkan riwayat kelas.
     */
    public function revokeGraduation(Student $student, ?string $reason = null): Student
    {
        if ($student->status !== 'Lulus') {
            throw new \InvalidArgumentException('Hanya siswa berstatus Lulus yang dapat dibatalkan kelulusannya.');
        }

        if ($student->alumniDestinations()->exists()) {
            throw new \InvalidArgumentException(
                'Tidak dapat membatalkan kelulusan: masih ada data destinasi alumni. Hapus destinasi terlebih dahulu.'
            );
        }

        if ($student->documentPickups()->exists()) {
            throw new \InvalidArgumentException(
                'Tidak dapat membatalkan kelulusan: sudah ada catatan pengambilan ijazah/dokumen.'
            );
        }

        return DB::transaction(function () use ($student, $reason) {
            $this->restoreClassHistoryAfterRevoke($student);

            $student->update([
                'status' => 'Aktif',
                'graduation_year' => null,
            ]);

            Log::info('Student graduation revoked', [
                'student_id' => $student->id,
                'class_id' => $student->class_id,
                'reason' => $reason,
            ]);

            return $student->fresh(['institution', 'class', 'academicYear', 'semester']);
        });
    }

    /**
     * Batalkan kelulusan banyak siswa sekaligus.
     *
     * @param  array<int>  $studentIds
     * @return array{success: int, failed: array<array{id: int, reason: string}>}
     */
    public function revokeGraduationBulk(array $studentIds, ?string $reason = null): array
    {
        $success = 0;
        $failed = [];

        foreach ($studentIds as $id) {
            $student = Student::find($id);
            if (! $student) {
                $failed[] = ['id' => $id, 'reason' => 'Siswa tidak ditemukan.'];

                continue;
            }
            try {
                $this->revokeGraduation($student, $reason);
                $success++;
            } catch (\Throwable $e) {
                $failed[] = ['id' => $id, 'reason' => $e->getMessage()];
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Hapus baris history status Lulus dan buka kembali history aktif sebelumnya.
     */
    protected function restoreClassHistoryAfterRevoke(Student $student): void
    {
        $classId = $student->class_id ? (int) $student->class_id : null;
        $academicYearId = $student->academic_year_id ? (int) $student->academic_year_id : null;

        $lulusQuery = ClassStudentHistory::query()
            ->where('student_id', $student->id)
            ->where('status', 'Lulus');

        if ($classId) {
            $lulusQuery->where('class_id', $classId);
        }
        if ($academicYearId) {
            $lulusQuery->where('academic_year_id', $academicYearId);
        }

        $lulusRows = $lulusQuery->orderByDesc('id')->get();
        if ($lulusRows->isEmpty()) {
            // Fallback: hapus history Lulus terbaru siswa (jika class/year tidak sinkron)
            $fallback = ClassStudentHistory::query()
                ->where('student_id', $student->id)
                ->where('status', 'Lulus')
                ->orderByDesc('id')
                ->first();
            if ($fallback) {
                $classId = (int) $fallback->class_id;
                $academicYearId = (int) $fallback->academic_year_id;
                $fallback->delete();
            }
        } else {
            foreach ($lulusRows as $row) {
                $row->delete();
            }
        }

        if (! $classId || ! $academicYearId) {
            return;
        }

        $previous = ClassStudentHistory::query()
            ->where('student_id', $student->id)
            ->where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->whereNotNull('end_date')
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();

        if ($previous) {
            $previous->update([
                'end_date' => null,
                'status' => 'Aktif',
                'notes' => trim(($previous->notes ? $previous->notes.' | ' : '').'Dibuka kembali setelah batal lulus'),
            ]);
        }
    }

    /**
     * Infer graduation year from student's academic year (e.g. "2024/2025" -> 2025).
     */
    protected function inferGraduationYearFromStudent(Student $student): int
    {
        if ($student->graduation_year) {
            return (int) $student->graduation_year;
        }
        if ($student->academic_year_id) {
            $ay = \App\Models\AcademicYear::find($student->academic_year_id);
            if ($ay && preg_match('/^\d{4}/', $ay->name ?? '', $m)) {
                return (int) $m[0] + 1; // e.g. 2024/2025 -> 2025
            }
        }

        return (int) date('Y');
    }

    /**
     * Daftar tahun lulus yang ada (untuk filter dropdown).
     *
     * @return array<int>
     */
    public function getGraduationYears(?int $institutionId = null): array
    {
        $query = Student::query()->alumni()->whereNotNull('graduation_year');
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        return $query->select('graduation_year')->distinct()->orderByDesc('graduation_year')->pluck('graduation_year')->map(fn ($y) => (int) $y)->values()->all();
    }

    /**
     * Naik kelas: pindahkan siswa dari kelas/tahun ajaran sumber ke kelas/tahun ajaran tujuan.
     * Hanya siswa dengan status Aktif. Riwayat kelas otomatis tercatat dengan status naik_kelas.
     *
     * @param  array<int>|null  $studentIds  Jika null, semua siswa Aktif di kelas sumber akan dinaikkan.
     * @return array{success: int, failed: array<array{id: int, reason: string}>}
     */
    public function promoteBulk(
        int $institutionId,
        int $sourceClassId,
        int $sourceAcademicYearId,
        int $targetClassId,
        int $targetAcademicYearId,
        ?int $targetSemesterId = null,
        ?array $studentIds = null
    ): array {
        $targetClass = \App\Models\SchoolClass::where('id', $targetClassId)
            ->where('institution_id', $institutionId)
            ->where('academic_year_id', $targetAcademicYearId)
            ->first();
        if (! $targetClass) {
            throw new \InvalidArgumentException('Kelas tujuan tidak ditemukan atau tidak sesuai tahun ajaran.');
        }

        $targetAcademicYear = \App\Models\AcademicYear::find($targetAcademicYearId);
        if (! $targetAcademicYear) {
            throw new \InvalidArgumentException('Tahun ajaran tujuan tidak ditemukan.');
        }

        $targetSemesterId = $targetSemesterId ?? \App\Models\Semester::where('academic_year_id', $targetAcademicYearId)->orderBy('id')->value('id');
        if ($targetSemesterId && ! \App\Models\Semester::where('id', $targetSemesterId)->where('academic_year_id', $targetAcademicYearId)->exists()) {
            throw new \InvalidArgumentException('Semester tujuan tidak termasuk dalam tahun ajaran tujuan.');
        }

        $query = Student::query()
            ->where('institution_id', $institutionId)
            ->where('class_id', $sourceClassId)
            ->where('academic_year_id', $sourceAcademicYearId)
            ->where('status', 'Aktif');

        if ($studentIds !== null && count($studentIds) > 0) {
            $query->whereIn('id', $studentIds);
        }

        $students = $query->get();
        $success = 0;
        $failed = [];

        $updateData = [
            'class_id' => $targetClassId,
            'tingkat' => $targetClass->grade,
            'academic_year_id' => $targetAcademicYearId,
            'class' => $targetClass->name,
            'academic_year' => $targetAcademicYear->code ?: $targetAcademicYear->name,
        ];
        if ($targetSemesterId) {
            $updateData['semester_id'] = $targetSemesterId;
        }

        foreach ($students as $student) {
            try {
                $this->update($student, $updateData);
                $success++;
            } catch (\Throwable $e) {
                $failed[] = ['id' => $student->id, 'reason' => $e->getMessage()];
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Check if student can access (authorization check).
     */
    public function canAccess(Student $student, ?int $userInstitutionId, bool $isAdminOrSuperAdmin): bool
    {
        if ($isAdminOrSuperAdmin) {
            return true;
        }

        if ($userInstitutionId === null) {
            return false;
        }

        return (int) $student->institution_id === (int) $userInstitutionId;
    }
}

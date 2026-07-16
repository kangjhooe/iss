<?php

namespace App\Services;

use App\Models\Student;
use App\Models\ClassStudentHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class StudentService
{
    /**
     * Get list of students with filters.
     */
    public function list(array $filters, ?int $institutionId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Student::query();

        if (!empty($filters['only_trashed'])) {
            $query->onlyTrashed();
        } elseif (!empty($filters['with_trashed'])) {
            $query->withTrashed();
        }

        // Filter by institution if provided
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('nik', 'like', '%' . $search . '%')
                  ->orWhere('nis', 'like', '%' . $search . '%')
                  ->orWhere('nisn', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['class'])) {
            $query->where('class', $filters['class']);
        }

        if (isset($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        if (!empty($filters['class_ids']) && is_array($filters['class_ids'])) {
            $query->whereIn('class_id', $filters['class_ids']);
        }

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (isset($filters['semester_id'])) {
            $query->where('semester_id', $filters['semester_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        $perPage = min($perPage, 100); // Max 100 per page

        // Include graduation_year for list
        return $query->select(['id', 'institution_id', 'nik', 'nis', 'nisn', 'name', 'gender', 'class', 'class_id', 'academic_year', 'academic_year_id', 'semester_id', 'status', 'graduation_year', 'created_at'])
            ->with([
                'institution:id,name,npsn',
                'class:id,name,grade,academic_year_id',
                'academicYear:id,name,code',
                'semester:id,name,academic_year_id'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new student.
     */
    public function create(array $data): Student
    {
        $student = Student::create($data);

        // Create initial class history if class_id is provided
        if (isset($data['class_id']) && isset($data['academic_year_id'])) {
            $semesterId = $data['semester_id'] ?? null;
            $this->createClassHistory($student, $data['class_id'], $data['academic_year_id'], $semesterId, 'masuk');
        }

        Log::info('Student created', [
            'student_id' => $student->id,
            'institution_id' => $student->institution_id,
        ]);

        return $student->load(['institution', 'class', 'academicYear', 'semester']);
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

        Log::info('Student updated', [
            'student_id' => $student->id,
            'class_changed' => $classChanged,
            'academic_year_changed' => $academicYearChanged,
            'status_changed' => $statusChanged,
        ]);

        return $student->fresh(['institution', 'class', 'academicYear', 'semester', 'documents']);
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
     * Create class history record.
     */
    protected function createClassHistory(Student $student, ?int $classId, ?int $academicYearId, ?int $semesterId = null, string $status = 'masuk'): ?ClassStudentHistory
    {
        if (!$classId || !$academicYearId) {
            return null;
        }

        // Load class and academic year if not already loaded
        $class = $student->class_id == $classId ? $student->class : \App\Models\SchoolClass::find($classId);
        $academicYear = $student->academic_year_id == $academicYearId ? $student->academicYear : \App\Models\AcademicYear::find($academicYearId);

        if (!$class || !$academicYear) {
            Log::warning('Cannot create class history - class or academic year not found', [
                'student_id' => $student->id,
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
            ]);
            return null;
        }

        // Use semester_id from student if not provided
        if (!$semesterId) {
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
        if (!$classId || !$academicYearId) {
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

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('nisn', 'like', '%' . $search . '%')
                    ->orWhere('nis', 'like', '%' . $search . '%')
                    ->orWhere('nik', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['graduation_year'])) {
            $query->byGraduationYear((int) $filters['graduation_year']);
        }

        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        $perPage = min($perPage, 100);

        return $query
            ->select(['id', 'institution_id', 'nik', 'nis', 'nisn', 'name', 'gender', 'class', 'class_id', 'academic_year', 'academic_year_id', 'graduation_year', 'status', 'created_at'])
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

        if ($oldClassId && $oldAcademicYearId) {
            $this->endPreviousHistory($student->id, $oldClassId, $oldAcademicYearId);
            $this->createClassHistory($student, $oldClassId, $oldAcademicYearId, $student->semester_id, 'Lulus');
        }

        $student->update([
            'status' => 'Lulus',
            'graduation_year' => $year,
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
     * @param array<int> $studentIds
     * @return array{success: int, failed: array<array{id: int, reason: string}>}
     */
    public function graduateBulk(array $studentIds, ?int $graduationYear = null): array
    {
        $year = $graduationYear ?? (int) date('Y');
        $success = 0;
        $failed = [];

        foreach ($studentIds as $id) {
            $student = Student::find($id);
            if (!$student) {
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
     * @param array<int>|null $studentIds Jika null, semua siswa Aktif di kelas sumber akan dinaikkan.
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
        if (!$targetClass) {
            throw new \InvalidArgumentException('Kelas tujuan tidak ditemukan atau tidak sesuai tahun ajaran.');
        }

        $targetAcademicYear = \App\Models\AcademicYear::find($targetAcademicYearId);
        if (!$targetAcademicYear) {
            throw new \InvalidArgumentException('Tahun ajaran tujuan tidak ditemukan.');
        }

        $targetSemesterId = $targetSemesterId ?? \App\Models\Semester::where('academic_year_id', $targetAcademicYearId)->orderBy('id')->value('id');
        if ($targetSemesterId && !\App\Models\Semester::where('id', $targetSemesterId)->where('academic_year_id', $targetAcademicYearId)->exists()) {
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
            'academic_year_id' => $targetAcademicYearId,
            'class' => $targetClass->name,
            'academic_year' => $targetAcademicYear->name,
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

        return $student->institution_id === $userInstitutionId;
    }
}

<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\SchoolClass;
use App\Repositories\ClassRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ClassService
{
    public function __construct(
        protected ClassRepository $classRepository,
        protected WaliKelasPermissionService $waliKelasPermissionService
    ) {}

    /**
     * Get list of classes with filters.
     */
    public function list(array $filters, ?int $institutionId = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->classRepository->list($filters, $institutionId, $perPage);
    }

    /**
     * Create a new class.
     */
    public function create(array $data): SchoolClass
    {
        // Validate grade based on institution level
        $institution = Institution::findOrFail($data['institution_id']);
        $this->validateGradeForInstitutionLevel($data['grade'] ?? null, $institution->level);

        // Auto-fill academic_year from academic_year_id if not provided
        if (isset($data['academic_year_id']) && !isset($data['academic_year'])) {
            $academicYear = \App\Models\AcademicYear::find($data['academic_year_id']);
            if ($academicYear) {
                $data['academic_year'] = $academicYear->code;
            }
        }

        // Validate teacher is not already a wali kelas (check by semester if semester_id provided, otherwise by academic_year)
        if (isset($data['teacher_id']) && $data['teacher_id']) {
            $academicYearId = $data['academic_year_id'] ?? null;
            $semesterId = $data['semester_id'] ?? null;
            
            if ($academicYearId) {
                // If semester_id provided, check within same semester
                if ($semesterId) {
                    $existingClass = $this->classRepository->query()
                        ->where('teacher_id', $data['teacher_id'])
                        ->where('academic_year_id', $academicYearId)
                        ->where('semester_id', $semesterId)
                        ->where('status', 'Aktif')
                        ->first();
                } else {
                    // Otherwise check within same academic year
                    if ($this->classRepository->isTeacherAlreadyWaliKelas($data['teacher_id'], $academicYearId)) {
                        throw ValidationException::withMessages([
                            'teacher_id' => 'Guru ini sudah menjadi wali kelas untuk kelas lain di tahun ajaran yang sama.'
                        ]);
                    }
                }
            }
        }

        // Validate room is not already used (check by semester if semester_id provided, otherwise by academic_year)
        if (isset($data['room_id']) && $data['room_id']) {
            $academicYearId = $data['academic_year_id'] ?? null;
            $semesterId = $data['semester_id'] ?? null;
            
            if ($academicYearId) {
                // If semester_id provided, check within same semester
                if ($semesterId) {
                    $existingClass = $this->classRepository->query()
                        ->where('room_id', $data['room_id'])
                        ->where('academic_year_id', $academicYearId)
                        ->where('semester_id', $semesterId)
                        ->where('status', 'Aktif')
                        ->first();
                    
                    if ($existingClass) {
                        throw ValidationException::withMessages([
                            'room_id' => 'Ruangan ini sudah digunakan oleh kelas lain di semester yang sama.'
                        ]);
                    }
                } else {
                    // Otherwise check within same academic year
                    if ($this->classRepository->isRoomAlreadyUsed($data['room_id'], $academicYearId)) {
                        throw ValidationException::withMessages([
                            'room_id' => 'Ruangan ini sudah digunakan oleh kelas lain di tahun ajaran yang sama.'
                        ]);
                    }
                }
            }
        }

        $class = $this->classRepository->create($data);

        if (!empty($data['teacher_id'])) {
            $this->waliKelasPermissionService->grantWaliKelasPermissionsToEmployee((int) $data['teacher_id']);
        }

        Log::info('Class created', [
            'class_id' => $class->id,
            'institution_id' => $class->institution_id,
        ]);

        return $class;
    }

    /**
     * Get class by ID.
     */
    public function find(int $id): SchoolClass
    {
        return $this->classRepository->findWithRelations($id);
    }

    /**
     * Update class.
     */
    public function update(SchoolClass $class, array $data): SchoolClass
    {
        // Validate grade based on institution level
        $institution = $class->institution;
        if (isset($data['grade'])) {
            $this->validateGradeForInstitutionLevel($data['grade'], $institution->level);
        }

        // Validate teacher is not already a wali kelas (exclude current class)
        if (isset($data['teacher_id']) && $data['teacher_id']) {
            $academicYearId = $data['academic_year_id'] ?? $class->academic_year_id;
            $semesterId = $data['semester_id'] ?? $class->semester_id;
            
            if ($academicYearId) {
                // If semester_id provided, check within same semester
                if ($semesterId) {
                    $existingClass = $this->classRepository->query()
                        ->where('teacher_id', $data['teacher_id'])
                        ->where('academic_year_id', $academicYearId)
                        ->where('semester_id', $semesterId)
                        ->where('status', 'Aktif')
                        ->where('id', '!=', $class->id)
                        ->first();
                    
                    if ($existingClass) {
                        throw ValidationException::withMessages([
                            'teacher_id' => 'Guru ini sudah menjadi wali kelas untuk kelas lain di semester yang sama.'
                        ]);
                    }
                } else {
                    // Otherwise check within same academic year
                    if ($this->classRepository->isTeacherAlreadyWaliKelas($data['teacher_id'], $academicYearId, $class->id)) {
                        throw ValidationException::withMessages([
                            'teacher_id' => 'Guru ini sudah menjadi wali kelas untuk kelas lain di tahun ajaran yang sama.'
                        ]);
                    }
                }
            }
        }

        // Validate room is not already used (exclude current class)
        if (isset($data['room_id']) && $data['room_id']) {
            $academicYearId = $data['academic_year_id'] ?? $class->academic_year_id;
            $semesterId = $data['semester_id'] ?? $class->semester_id;
            
            if ($academicYearId) {
                // If semester_id provided, check within same semester
                if ($semesterId) {
                    $existingClass = $this->classRepository->query()
                        ->where('room_id', $data['room_id'])
                        ->where('academic_year_id', $academicYearId)
                        ->where('semester_id', $semesterId)
                        ->where('status', 'Aktif')
                        ->where('id', '!=', $class->id)
                        ->first();
                    
                    if ($existingClass) {
                        throw ValidationException::withMessages([
                            'room_id' => 'Ruangan ini sudah digunakan oleh kelas lain di semester yang sama.'
                        ]);
                    }
                } else {
                    // Otherwise check within same academic year
                    if ($this->classRepository->isRoomAlreadyUsed($data['room_id'], $academicYearId, $class->id)) {
                        throw ValidationException::withMessages([
                            'room_id' => 'Ruangan ini sudah digunakan oleh kelas lain di tahun ajaran yang sama.'
                        ]);
                    }
                }
            }
        }

        // Ensure academic_year is synced with academic_year_id if academic_year_id exists
        $academicYearId = $data['academic_year_id'] ?? $class->academic_year_id;
        if ($academicYearId && (!isset($data['academic_year']) || $data['academic_year'] !== $class->academic_year)) {
            $academicYear = \App\Models\AcademicYear::find($academicYearId);
            if ($academicYear) {
                $data['academic_year'] = $academicYear->code;
            }
        }

        $this->classRepository->update($class, $data);

        if (array_key_exists('teacher_id', $data) && !empty($data['teacher_id'])) {
            $this->waliKelasPermissionService->grantWaliKelasPermissionsToEmployee((int) $data['teacher_id']);
        }

        Log::info('Class updated', [
            'class_id' => $class->id,
        ]);

        return $class->fresh(['institution', 'room', 'teacher', 'students']);
    }

    /**
     * Delete class (soft delete).
     */
    public function delete(SchoolClass $class): bool
    {
        $classId = $class->id;
        $result = $this->classRepository->delete($class);

        Log::info('Class deleted', [
            'class_id' => $classId,
        ]);

        return $result;
    }

    /**
     * Validate grade based on institution level.
     */
    protected function validateGradeForInstitutionLevel(?int $grade, ?string $level): void
    {
        if ($grade === null) {
            // Grade can be null for PAUD (only A and B classes)
            if ($level !== 'PAUD' && $level !== 'TK') {
                throw ValidationException::withMessages([
                    'grade' => 'Tingkat kelas wajib diisi untuk jenjang ini.'
                ]);
            }
            return;
        }

        $validGrades = $this->getValidGradesForLevel($level);

        if ($validGrades === null) {
            // PAUD/TK doesn't use numeric grades
            throw ValidationException::withMessages([
                'grade' => 'Jenjang PAUD/TK tidak menggunakan tingkat numerik. Gunakan kelas A atau B.'
            ]);
        }

        if (!in_array($grade, $validGrades)) {
            throw ValidationException::withMessages([
                'grade' => "Tingkat kelas tidak valid untuk jenjang {$level}. Tingkat yang valid: " . implode(', ', $validGrades)
            ]);
        }
    }

    /**
     * Get valid grades for institution level.
     */
    protected function getValidGradesForLevel(?string $level): ?array
    {
        return match($level) {
            'SD', 'MI' => [1, 2, 3, 4, 5, 6],
            'SMP', 'MTs' => [7, 8, 9],
            'SMA', 'MA', 'MAK', 'SMK' => [10, 11, 12],
            'PAUD', 'TK' => null, // No numeric grades, only A and B
            default => null,
        };
    }

    /**
     * Get available grades for institution level.
     */
    public function getAvailableGrades(?string $level): ?array
    {
        return $this->getValidGradesForLevel($level);
    }
}

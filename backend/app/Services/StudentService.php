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

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        $perPage = min($perPage, 100); // Max 100 per page

        // Optimize eager loading - only load necessary relationships
        return $query->select(['id', 'institution_id', 'nik', 'nis', 'nisn', 'name', 'gender', 'class', 'class_id', 'academic_year', 'academic_year_id', 'status', 'created_at'])
            ->with([
                'institution:id,name,npsn',
                'class:id,name,grade,academic_year_id',
                'academicYear:id,name,code'
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
            $this->createClassHistory($student, $data['class_id'], $data['academic_year_id'], 'masuk');
        }

        Log::info('Student created', [
            'student_id' => $student->id,
            'institution_id' => $student->institution_id,
        ]);

        return $student->load(['institution', 'class', 'academicYear']);
    }

    /**
     * Get student by ID with relationships.
     */
    public function find(int $id, array $with = ['institution', 'documents', 'class', 'academicYear', 'classHistory']): Student
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
                $this->createClassHistory($student, $newClassId, $newAcademicYearId, $historyStatus);
            }
        }

        Log::info('Student updated', [
            'student_id' => $student->id,
            'class_changed' => $classChanged,
            'academic_year_changed' => $academicYearChanged,
            'status_changed' => $statusChanged,
        ]);

        return $student->fresh(['institution', 'class', 'academicYear', 'documents']);
    }

    /**
     * Determine history status based on changes.
     */
    protected function determineHistoryStatus(bool $classChanged, bool $academicYearChanged, bool $statusChanged, string $newStatus): string
    {
        // Priority: status change > class change > academic year change
        if ($statusChanged && in_array($newStatus, ['Lulus', 'Pindah', 'Drop Out'])) {
            return strtolower(str_replace(' ', '_', $newStatus));
        }

        if ($classChanged) {
            return 'pindah';
        }

        if ($academicYearChanged) {
            return 'naik_kelas';
        }

        return 'update';
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
    protected function createClassHistory(Student $student, ?int $classId, ?int $academicYearId, string $status = 'masuk'): ?ClassStudentHistory
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

        return ClassStudentHistory::create([
            'student_id' => $student->id,
            'class_id' => $classId,
            'academic_year' => $academicYear->name ?? $student->academic_year,
            'academic_year_id' => $academicYearId,
            'start_date' => now(),
            'status' => $status,
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

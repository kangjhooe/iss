<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Violation;
use App\Models\ViolationType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ViolationService
{
    /**
     * List violations for institution with filters.
     */
    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Violation::with([
            'student' => fn ($q) => $q->select('id', 'name', 'nis', 'nisn', 'class_id')->with('class:id,name'),
            'violationType:id,name,code,category,point_weight,default_sanction',
            'reporter:id,name,email',
        ])
            ->forInstitution($institutionId)
            ->orderBy('violation_date', 'desc')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }
        if (!empty($filters['violation_type_id'])) {
            $query->where('violation_type_id', $filters['violation_type_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('violation_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('violation_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }
        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }
        if (!empty($filters['semester_id'])) {
            $query->where('semester_id', $filters['semester_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get violations by student.
     */
    public function listByStudent(int $studentId, int $institutionId): LengthAwarePaginator
    {
        return Violation::with([
            'violationType:id,name,code,category,point_weight',
            'reporter:id,name',
        ])
            ->forInstitution($institutionId)
            ->forStudent($studentId)
            ->orderBy('violation_date', 'desc')
            ->paginate(20);
    }

    /**
     * Create violation.
     */
    public function create(int $institutionId, array $data, int $reportedBy): Violation
    {
        $student = Student::where('id', $data['student_id'])
            ->where('institution_id', $institutionId)
            ->firstOrFail();

        $violationType = ViolationType::where('id', $data['violation_type_id'])
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->firstOrFail();

        $violation = Violation::create([
            'institution_id' => $institutionId,
            'student_id' => $student->id,
            'violation_type_id' => $violationType->id,
            'reported_by' => $reportedBy,
            'violation_date' => $data['violation_date'],
            'sanction' => $data['sanction'] ?? $violationType->default_sanction,
            'status' => 'dicatat',
            'description' => $data['description'] ?? null,
            'academic_year_id' => $student->academic_year_id,
            'semester_id' => $student->semester_id,
            'class_id' => $student->class_id,
        ]);

        return $violation->load(['student', 'violationType', 'reporter']);
    }

    /**
     * Update violation.
     */
    public function update(Violation $violation, array $data): Violation
    {
        $violation->update($data);
        return $violation->fresh(['student', 'violationType', 'reporter']);
    }

    /**
     * List violation types for institution.
     */
    public function listTypes(int $institutionId, bool $activeOnly = true): Collection
    {
        $query = ViolationType::forInstitution($institutionId)->orderBy('category')->orderBy('name');
        if ($activeOnly) {
            $query->active();
        }
        return $query->get();
    }
}

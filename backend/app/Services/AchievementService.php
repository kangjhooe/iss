<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\AchievementType;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class AchievementService
{
    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Achievement::with([
            'student:id,name,nis,nisn,class_id',
            'achievementType:id,name,point_value',
            'giver:id,name',
            'reviewer:id,name',
            'academicYear:id,name,code',
            'semester:id,name',
        ])
            ->forInstitution($institutionId)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('achievement_date', 'desc')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }
        if (!empty($filters['achievement_type_id'])) {
            $query->where('achievement_type_id', $filters['achievement_type_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('achievement_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('achievement_date', '<=', $filters['date_to']);
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
        if (!empty($filters['given_by'])) {
            $query->where('given_by', $filters['given_by']);
        }
        if (!empty($filters['class_ids']) && is_array($filters['class_ids'])) {
            $query->whereHas('student', fn ($q) => $q->whereIn('class_id', $filters['class_ids']));
        }

        return $query->paginate($perPage);
    }

    public function create(int $institutionId, array $data, int $givenBy, bool $asPending = false): Achievement
    {
        $student = Student::where('id', $data['student_id'])
            ->where('institution_id', $institutionId)
            ->firstOrFail();

        $type = AchievementType::where('id', $data['achievement_type_id'])
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->firstOrFail();

        $institution = Institution::find($institutionId);
        $academicYearId = $student->academic_year_id ?: $institution?->active_academic_year_id;
        $semesterId = $student->semester_id ?: $institution?->active_semester_id;
        $pointValue = $data['point_value'] ?? $type->point_value;

        $status = $asPending ? Achievement::STATUS_PENDING : Achievement::STATUS_DICATAT;

        $achievement = Achievement::create([
            'institution_id' => $institutionId,
            'student_id' => $student->id,
            'achievement_type_id' => $type->id,
            'given_by' => $givenBy,
            'achievement_date' => $data['achievement_date'],
            'point_value' => $pointValue,
            'notes' => $data['notes'] ?? null,
            'status' => $status,
            'reviewed_by' => $asPending ? null : $givenBy,
            'reviewed_at' => $asPending ? null : now(),
            'academic_year_id' => $academicYearId,
            'semester_id' => $semesterId,
        ]);

        return $achievement->load([
            'student',
            'achievementType',
            'giver',
            'reviewer',
            'academicYear:id,name,code',
            'semester:id,name',
        ]);
    }

    public function approve(Achievement $achievement, User $reviewer, array $data = []): Achievement
    {
        if (!$achievement->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Hanya usulan menunggu yang dapat disetujui.'],
            ]);
        }

        $achievement->update([
            'status' => Achievement::STATUS_DICATAT,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
            'point_value' => $data['point_value'] ?? $achievement->point_value,
        ]);

        return $achievement->fresh([
            'student',
            'achievementType',
            'giver',
            'reviewer',
            'academicYear:id,name,code',
            'semester:id,name',
        ]);
    }

    public function reject(Achievement $achievement, User $reviewer, string $reviewNotes): Achievement
    {
        if (!$achievement->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Hanya usulan menunggu yang dapat ditolak.'],
            ]);
        }

        $achievement->update([
            'status' => Achievement::STATUS_DITOLAK,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $reviewNotes,
        ]);

        return $achievement->fresh([
            'student',
            'achievementType',
            'giver',
            'reviewer',
            'academicYear:id,name,code',
            'semester:id,name',
        ]);
    }
}

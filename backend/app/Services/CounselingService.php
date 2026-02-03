<?php

namespace App\Services;

use App\Models\CounselingSession;
use App\Models\CounselingType;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class CounselingService
{
    /**
     * List counseling sessions for institution with filters.
     */
    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CounselingSession::with([
            'student' => fn ($q) => $q->select('id', 'name', 'nis', 'nisn', 'class_id')->with('class:id,name'),
            'counselor:id,name,email',
            'counselingType:id,name,code,description',
        ])
            ->forInstitution($institutionId)
            ->orderBy('session_date', 'desc')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }
        if (!empty($filters['counselor_id'])) {
            $query->where('counselor_id', $filters['counselor_id']);
        }
        if (!empty($filters['counseling_type_id'])) {
            $query->where('counseling_type_id', $filters['counseling_type_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('session_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('session_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }
        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }
        if (!empty($filters['semester_id'])) {
            $query->where('semester_id', $filters['semester_id']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * List counseling sessions for export (no pagination, same filters).
     */
    public function listForExport(int $institutionId, array $filters = [], int $limit = 5000): Collection
    {
        $query = CounselingSession::with([
            'student' => fn ($q) => $q->select('id', 'name', 'nis', 'nisn', 'class_id')->with('class:id,name'),
            'counselor:id,name,email',
            'counselingType:id,name,code,description',
        ])
            ->forInstitution($institutionId)
            ->orderBy('session_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }
        if (!empty($filters['counselor_id'])) {
            $query->where('counselor_id', $filters['counselor_id']);
        }
        if (!empty($filters['counseling_type_id'])) {
            $query->where('counseling_type_id', $filters['counseling_type_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('session_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('session_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }
        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }
        if (!empty($filters['semester_id'])) {
            $query->where('semester_id', $filters['semester_id']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    /**
     * Get counseling sessions by student.
     */
    public function listByStudent(int $studentId, int $institutionId): LengthAwarePaginator
    {
        return CounselingSession::with([
            'counselingType:id,name,code,description',
            'counselor:id,name',
        ])
            ->forInstitution($institutionId)
            ->forStudent($studentId)
            ->orderBy('session_date', 'desc')
            ->paginate(20);
    }

    /**
     * Create counseling session.
     */
    public function create(int $institutionId, array $data, int $counselorId): CounselingSession
    {
        $student = Student::where('id', $data['student_id'])
            ->where('institution_id', $institutionId)
            ->firstOrFail();

        $counselingTypeId = $data['counseling_type_id'] ?? null;
        if ($counselingTypeId) {
            CounselingType::where('id', $counselingTypeId)
                ->where('institution_id', $institutionId)
                ->where('is_active', true)
                ->firstOrFail();
        }

        $counselorIdInput = $data['counselor_id'] ?? $counselorId;

        $session = CounselingSession::create([
            'institution_id' => $institutionId,
            'student_id' => $student->id,
            'counselor_id' => $counselorIdInput,
            'counseling_type_id' => $counselingTypeId,
            'session_date' => $data['session_date'],
            'status' => $data['status'] ?? 'jadwal',
            'summary' => $data['summary'] ?? null,
            'follow_up_notes' => $data['follow_up_notes'] ?? null,
            'academic_year_id' => $student->academic_year_id,
            'semester_id' => $student->semester_id,
            'class_id' => $student->class_id,
        ]);

        return $session->load(['student', 'counselor', 'counselingType']);
    }

    /**
     * Update counseling session.
     */
    public function update(CounselingSession $session, array $data): CounselingSession
    {
        $session->update($data);
        return $session->fresh(['student', 'counselor', 'counselingType']);
    }

    /**
     * List counseling types for institution.
     */
    public function listTypes(int $institutionId, bool $activeOnly = true): Collection
    {
        $query = CounselingType::forInstitution($institutionId)->orderBy('name');
        if ($activeOnly) {
            $query->active();
        }
        return $query->get();
    }

    /**
     * Get dashboard stats: sessions per month and per type for a given year.
     */
    public function getStats(int $institutionId, ?int $year = null): array
    {
        $year = $year ?? (int) Carbon::now()->format('Y');
        $start = "{$year}-01-01";
        $end = "{$year}-12-31";

        $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $byMonth = CounselingSession::forInstitution($institutionId)
            ->whereDate('session_date', '>=', $start)
            ->whereDate('session_date', '<=', $end)
            ->selectRaw('MONTH(session_date) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months = [];
        foreach (range(1, 12) as $m) {
            $months[] = [
                'month' => $m,
                'label' => $monthNames[$m - 1],
                'count' => (int) ($byMonth->get($m)?->count ?? 0),
            ];
        }

        $byType = CounselingSession::forInstitution($institutionId)
            ->whereDate('session_date', '>=', $start)
            ->whereDate('session_date', '<=', $end)
            ->leftJoin('counseling_types', 'counseling_sessions.counseling_type_id', '=', 'counseling_types.id')
            ->selectRaw('COALESCE(counseling_types.name, "Tanpa jenis") as name, COUNT(*) as count')
            ->groupBy('name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'count' => (int) $r->count])
            ->values()
            ->all();

        $totalThisMonth = CounselingSession::forInstitution($institutionId)
            ->whereMonth('session_date', Carbon::now()->month)
            ->whereYear('session_date', Carbon::now()->year)
            ->count();

        return [
            'year' => $year,
            'by_month' => $months,
            'by_type' => $byType,
            'total_this_month' => $totalThisMonth,
        ];
    }

    /**
     * Get upcoming scheduled sessions (status = jadwal, session_date >= today).
     */
    public function getUpcoming(int $institutionId, int $limit = 10): Collection
    {
        $today = Carbon::now()->toDateString();

        return CounselingSession::with([
            'student' => fn ($q) => $q->select('id', 'name', 'nis', 'nisn')->with('class:id,name'),
            'counselor:id,name',
            'counselingType:id,name',
        ])
            ->forInstitution($institutionId)
            ->byStatus('jadwal')
            ->whereDate('session_date', '>=', $today)
            ->orderBy('session_date')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }
}

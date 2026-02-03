<?php

namespace App\Services;

use App\Models\TeachingJournal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TeachingJournalService
{
    /**
     * List teaching journals for institution with filters.
     * If employeeId is provided (teacher), filter to that teacher's entries only.
     */
    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 15, ?int $employeeId = null): LengthAwarePaginator
    {
        $query = TeachingJournal::with([
            'semester:id,name',
            'schoolClass:id,name',
            'subject:id,name,code',
            'employee:id,name',
        ])
            ->forInstitution($institutionId)
            ->orderBy('journal_date', 'desc')
            ->orderBy('period', 'asc')
            ->orderBy('created_at', 'desc');

        if ($employeeId !== null) {
            $query->forEmployee($employeeId);
        }
        if (!empty($filters['semester_id'])) {
            $query->forSemester((int) $filters['semester_id']);
        }
        if (!empty($filters['class_id'])) {
            $query->forClass((int) $filters['class_id']);
        }
        if (!empty($filters['employee_id'])) {
            $query->forEmployee((int) $filters['employee_id']);
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->dateFrom($filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->dateTo($filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * List teaching journals for export (no pagination).
     */
    public function listForExport(int $institutionId, array $filters = [], int $limit = 5000, ?int $employeeId = null): Collection
    {
        $query = TeachingJournal::with([
            'semester:id,name',
            'schoolClass:id,name',
            'subject:id,name,code',
            'employee:id,name',
        ])
            ->forInstitution($institutionId)
            ->orderBy('journal_date', 'desc')
            ->orderBy('period', 'asc')
            ->limit($limit);

        if ($employeeId !== null) {
            $query->forEmployee($employeeId);
        }
        if (!empty($filters['semester_id'])) {
            $query->forSemester((int) $filters['semester_id']);
        }
        if (!empty($filters['class_id'])) {
            $query->forClass((int) $filters['class_id']);
        }
        if (!empty($filters['employee_id'])) {
            $query->forEmployee((int) $filters['employee_id']);
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->dateFrom($filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->dateTo($filters['date_to']);
        }

        return $query->get();
    }

    /**
     * Create a teaching journal entry.
     */
    public function create(int $institutionId, array $data): TeachingJournal
    {
        return DB::transaction(function () use ($institutionId, $data) {
            $journal = new TeachingJournal();
            $journal->institution_id = $institutionId;
            $journal->semester_id = $data['semester_id'];
            $journal->lesson_schedule_id = $data['lesson_schedule_id'] ?? null;
            $journal->class_id = $data['class_id'];
            $journal->subject_id = $data['subject_id'];
            $journal->employee_id = $data['employee_id'];
            $journal->journal_date = $data['journal_date'];
            $journal->period = $data['period'] ?? 1;
            $journal->material_taught = $data['material_taught'] ?? null;
            $journal->attendance_notes = $data['attendance_notes'] ?? null;
            $journal->notes = $data['notes'] ?? null;
            $journal->save();
            return $journal->load(['semester', 'schoolClass', 'subject', 'employee']);
        });
    }

    /**
     * Update a teaching journal entry.
     */
    public function update(TeachingJournal $journal, array $data): TeachingJournal
    {
        return DB::transaction(function () use ($journal, $data) {
            if (isset($data['semester_id'])) {
                $journal->semester_id = $data['semester_id'];
            }
            if (array_key_exists('lesson_schedule_id', $data)) {
                $journal->lesson_schedule_id = $data['lesson_schedule_id'];
            }
            if (isset($data['class_id'])) {
                $journal->class_id = $data['class_id'];
            }
            if (isset($data['subject_id'])) {
                $journal->subject_id = $data['subject_id'];
            }
            if (isset($data['employee_id'])) {
                $journal->employee_id = $data['employee_id'];
            }
            if (isset($data['journal_date'])) {
                $journal->journal_date = $data['journal_date'];
            }
            if (array_key_exists('period', $data)) {
                $journal->period = $data['period'] ?? 1;
            }
            if (array_key_exists('material_taught', $data)) {
                $journal->material_taught = $data['material_taught'];
            }
            if (array_key_exists('attendance_notes', $data)) {
                $journal->attendance_notes = $data['attendance_notes'];
            }
            if (array_key_exists('notes', $data)) {
                $journal->notes = $data['notes'];
            }
            $journal->save();
            return $journal->load(['semester', 'schoolClass', 'subject', 'employee']);
        });
    }
}

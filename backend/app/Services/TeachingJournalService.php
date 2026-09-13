<?php

namespace App\Services;

use App\Models\TeachingJournal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TeachingJournalService
{
    public function __construct(
        protected LessonScheduleService $lessonScheduleService
    ) {}

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
            $resolved = $this->resolveScheduleLink($institutionId, $data);

            $journal = new TeachingJournal();
            $journal->institution_id = $institutionId;
            $journal->semester_id = $resolved['semester_id'];
            $journal->lesson_schedule_id = $resolved['lesson_schedule_id'];
            $journal->class_id = $resolved['class_id'];
            $journal->subject_id = $resolved['subject_id'];
            $journal->employee_id = $resolved['employee_id'];
            $journal->journal_date = $data['journal_date'];
            $journal->period = $resolved['period'];
            $journal->material_taught = $data['material_taught'] ?? null;
            $journal->attendance_notes = $data['attendance_notes'] ?? null;
            $journal->notes = $data['notes'] ?? null;
            if (array_key_exists('penilaian_index', $data) && $data['penilaian_index'] !== null && $data['penilaian_index'] !== '') {
                $journal->penilaian_index = (int) $data['penilaian_index'];
            }
            $journal->save();
            return $journal->load(['semester', 'schoolClass', 'subject', 'employee', 'lessonSchedule']);
        });
    }

    /**
     * Update a teaching journal entry.
     */
    public function update(TeachingJournal $journal, array $data): TeachingJournal
    {
        return DB::transaction(function () use ($journal, $data) {
            $merged = [
                'semester_id' => $data['semester_id'] ?? $journal->semester_id,
                'class_id' => $data['class_id'] ?? $journal->class_id,
                'subject_id' => $data['subject_id'] ?? $journal->subject_id,
                'employee_id' => $data['employee_id'] ?? $journal->employee_id,
                'period' => array_key_exists('period', $data) ? ($data['period'] ?? 1) : $journal->period,
                'lesson_schedule_id' => array_key_exists('lesson_schedule_id', $data)
                    ? $data['lesson_schedule_id']
                    : $journal->lesson_schedule_id,
            ];

            $resolved = $this->resolveScheduleLink((int) $journal->institution_id, $merged);

            $journal->semester_id = $resolved['semester_id'];
            $journal->lesson_schedule_id = $resolved['lesson_schedule_id'];
            $journal->class_id = $resolved['class_id'];
            $journal->subject_id = $resolved['subject_id'];
            $journal->employee_id = $resolved['employee_id'];
            $journal->period = $resolved['period'];

            if (isset($data['journal_date'])) {
                $journal->journal_date = $data['journal_date'];
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
            if (array_key_exists('penilaian_index', $data)) {
                $journal->penilaian_index = $data['penilaian_index'] !== null && $data['penilaian_index'] !== ''
                    ? (int) $data['penilaian_index']
                    : null;
            }
            $journal->save();
            return $journal->load(['semester', 'schoolClass', 'subject', 'employee', 'lessonSchedule']);
        });
    }

    /**
     * Ensure journal matches a teaching schedule; auto-link lesson_schedule_id.
     *
     * @param  array<string, mixed>  $data
     * @return array{semester_id:int,class_id:int,subject_id:int,employee_id:int,period:int,lesson_schedule_id:int}
     */
    private function resolveScheduleLink(int $institutionId, array $data): array
    {
        $semesterId = (int) $data['semester_id'];
        $classId = (int) $data['class_id'];
        $subjectId = (int) $data['subject_id'];
        $employeeId = (int) $data['employee_id'];
        $period = isset($data['period']) && $data['period'] !== null && $data['period'] !== ''
            ? (int) $data['period']
            : 1;

        if (!empty($data['lesson_schedule_id'])) {
            $schedule = \App\Models\LessonSchedule::query()
                ->where('id', (int) $data['lesson_schedule_id'])
                ->where('institution_id', $institutionId)
                ->first();

            if (!$schedule) {
                throw new \InvalidArgumentException('Slot jadwal tidak ditemukan di institusi ini.');
            }

            if ((int) $schedule->employee_id !== $employeeId
                || (int) $schedule->semester_id !== $semesterId
                || (int) $schedule->class_id !== $classId
                || (int) $schedule->subject_id !== $subjectId
            ) {
                throw new \InvalidArgumentException('Slot jadwal tidak cocok dengan kelas, mapel, semester, atau guru.');
            }

            return [
                'semester_id' => $semesterId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'employee_id' => $employeeId,
                'period' => $period ?: (int) $schedule->period,
                'lesson_schedule_id' => (int) $schedule->id,
            ];
        }

        $schedule = $this->lessonScheduleService->findMatchingSchedule(
            $institutionId,
            $semesterId,
            $classId,
            $subjectId,
            $employeeId,
            $period
        );

        if (!$schedule) {
            throw new \InvalidArgumentException(
                'Guru tidak dijadwalkan mengajar mapel ini di kelas tersebut pada semester ini. Periksa jadwal pelajaran.'
            );
        }

        return [
            'semester_id' => $semesterId,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'employee_id' => $employeeId,
            'period' => $period,
            'lesson_schedule_id' => (int) $schedule->id,
        ];
    }
}

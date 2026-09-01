<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LessonSchedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\TeachingJournal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentAttendanceService
{
    /**
     * Prepare attendance session from one or more consecutive lesson schedule slots + date.
     * Auto-creates empty teaching journals when none exist yet.
     *
     * @param  array<int, int>  $lessonScheduleIds
     * @return array{
     *   day_mismatch: bool,
     *   day_mismatch_message: string|null,
     *   schedule: array,
     *   schedules: array<int, array>,
     *   periods: array<int, int>,
     *   teaching_journal: TeachingJournal,
     *   teaching_journals: array<int, TeachingJournal>,
     *   attendances: Collection
     * }
     */
    public function prepareFromSchedule(
        array $lessonScheduleIds,
        string $date,
        int $institutionId,
        ?int $scopeEmployeeId = null
    ): array {
        $schedules = $this->resolveSchedulesForAttendance($lessonScheduleIds, $institutionId, $scopeEmployeeId);
        $dayCheck = $this->buildDayMismatchWarning($schedules->first(), $date);

        $journals = [];
        foreach ($schedules as $schedule) {
            $journals[] = $this->findOrCreateEmptyJournalForSchedule($schedule, $date, $institutionId);
        }

        $sourceJournal = $this->pickAttendanceSourceJournal($journals, $institutionId);
        $attendances = $this->listByTeachingJournal((int) $sourceJournal->id, $institutionId);

        return [
            'day_mismatch' => $dayCheck['mismatch'],
            'day_mismatch_message' => $dayCheck['message'],
            'schedule' => $this->scheduleSummary($schedules->first(), $schedules),
            'schedules' => $schedules->map(fn (LessonSchedule $s) => $this->scheduleSummary($s))->values()->all(),
            'periods' => $schedules->pluck('period')->map(fn ($p) => (int) $p)->values()->all(),
            'teaching_journal' => $sourceJournal,
            'teaching_journals' => $journals,
            'attendances' => $attendances,
        ];
    }

    /**
     * Save attendances for one or more consecutive schedule slots + date (auto journals).
     *
     * @param  array<int, int>  $lessonScheduleIds
     * @return array{
     *   day_mismatch: bool,
     *   day_mismatch_message: string|null,
     *   schedule: array,
     *   schedules: array<int, array>,
     *   periods: array<int, int>,
     *   teaching_journal: TeachingJournal,
     *   teaching_journals: array<int, TeachingJournal>,
     *   attendances: Collection
     * }
     */
    public function upsertFromSchedule(
        array $lessonScheduleIds,
        string $date,
        int $institutionId,
        array $attendances,
        ?int $scopeEmployeeId = null
    ): array {
        $schedules = $this->resolveSchedulesForAttendance($lessonScheduleIds, $institutionId, $scopeEmployeeId);
        $dayCheck = $this->buildDayMismatchWarning($schedules->first(), $date);

        $journals = [];
        $saved = collect();

        DB::transaction(function () use ($schedules, $date, $institutionId, $attendances, &$journals, &$saved) {
            foreach ($schedules as $schedule) {
                $journal = $this->findOrCreateEmptyJournalForSchedule($schedule, $date, $institutionId);
                $journals[] = $journal;
                $saved = $this->upsertForJournal((int) $journal->id, $institutionId, $attendances);
            }
        });

        $primary = $journals[0]->fresh(['schoolClass:id,name', 'subject:id,name', 'employee:id,name']);

        return [
            'day_mismatch' => $dayCheck['mismatch'],
            'day_mismatch_message' => $dayCheck['message'],
            'schedule' => $this->scheduleSummary($schedules->first(), $schedules),
            'schedules' => $schedules->map(fn (LessonSchedule $s) => $this->scheduleSummary($s))->values()->all(),
            'periods' => $schedules->pluck('period')->map(fn ($p) => (int) $p)->values()->all(),
            'teaching_journal' => $primary,
            'teaching_journals' => $journals,
            'attendances' => $saved,
        ];
    }

    /**
     * @return array{mismatch: bool, message: string|null}
     */
    public function buildDayMismatchWarning(LessonSchedule $schedule, string $date): array
    {
        return ['mismatch' => false, 'message' => null];
    }

    /**
     * @param  array<int, int>  $lessonScheduleIds
     * @return Collection<int, LessonSchedule>
     */
    protected function resolveSchedulesForAttendance(
        array $lessonScheduleIds,
        int $institutionId,
        ?int $scopeEmployeeId = null
    ): Collection {
        $ids = array_values(array_unique(array_map('intval', $lessonScheduleIds)));
        if ($ids === []) {
            throw new \InvalidArgumentException('Slot jadwal wajib dipilih.');
        }

        $schedules = LessonSchedule::query()
            ->with(['schoolClass:id,name', 'subject:id,name,code', 'employee:id,name'])
            ->whereIn('id', $ids)
            ->where('institution_id', $institutionId)
            ->get();

        if ($schedules->count() !== count($ids)) {
            throw new \InvalidArgumentException('Satu atau lebih slot jadwal tidak ditemukan.');
        }

        if ($scopeEmployeeId !== null) {
            foreach ($schedules as $schedule) {
                if ((int) $schedule->employee_id !== (int) $scopeEmployeeId) {
                    throw new \InvalidArgumentException('Slot jadwal bukan milik Anda.');
                }
            }
        }

        $sorted = $schedules->sortBy([
            ['day_of_week', 'asc'],
            ['period', 'asc'],
            ['id', 'asc'],
        ])->values();

        $this->assertConsecutiveAttendanceBlock($sorted);

        return $sorted;
    }

    /**
     * @param  Collection<int, LessonSchedule>  $schedules
     */
    protected function assertConsecutiveAttendanceBlock(Collection $schedules): void
    {
        if ($schedules->isEmpty()) {
            throw new \InvalidArgumentException('Slot jadwal wajib dipilih.');
        }

        $first = $schedules->first();
        foreach ($schedules as $schedule) {
            if (
                (int) $schedule->semester_id !== (int) $first->semester_id
                || (int) $schedule->class_id !== (int) $first->class_id
                || (int) $schedule->subject_id !== (int) $first->subject_id
                || (int) $schedule->employee_id !== (int) $first->employee_id
                || (int) $schedule->day_of_week !== (int) $first->day_of_week
            ) {
                throw new \InvalidArgumentException(
                    'Slot jadwal harus dari kelas, mapel, guru, dan hari yang sama untuk diisi sekaligus.'
                );
            }
        }

        $periods = $schedules->pluck('period')->map(fn ($p) => (int) $p)->values();
        for ($i = 1; $i < $periods->count(); $i++) {
            if ($periods[$i] !== $periods[$i - 1] + 1) {
                throw new \InvalidArgumentException(
                    'Slot jadwal harus berurutan (jam ke beruntun) untuk diisi sekaligus.'
                );
            }
        }
    }

    /**
     * Prefer journal that already has saved attendances; else earliest period.
     *
     * @param  array<int, TeachingJournal>  $journals
     */
    protected function pickAttendanceSourceJournal(array $journals, int $institutionId): TeachingJournal
    {
        $best = $journals[0];
        $bestCount = -1;

        foreach ($journals as $journal) {
            $count = StudentAttendance::query()
                ->forInstitution($institutionId)
                ->forTeachingJournal((int) $journal->id)
                ->count();
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $journal;
            }
        }

        return $best;
    }

    protected function findOrCreateEmptyJournalForSchedule(
        LessonSchedule $schedule,
        string $date,
        int $institutionId
    ): TeachingJournal {
        return DB::transaction(function () use ($schedule, $date, $institutionId) {
            $existing = TeachingJournal::query()
                ->forInstitution($institutionId)
                ->where('lesson_schedule_id', $schedule->id)
                ->whereDate('journal_date', $date)
                ->lockForUpdate()
                ->first();

            if (!$existing) {
                $existing = TeachingJournal::query()
                    ->forInstitution($institutionId)
                    ->where('class_id', $schedule->class_id)
                    ->where('subject_id', $schedule->subject_id)
                    ->where('employee_id', $schedule->employee_id)
                    ->where('period', $schedule->period)
                    ->whereDate('journal_date', $date)
                    ->lockForUpdate()
                    ->first();
            }

            if ($existing) {
                if (!$existing->lesson_schedule_id) {
                    $existing->lesson_schedule_id = $schedule->id;
                    $existing->save();
                }

                return $existing->load(['schoolClass:id,name', 'subject:id,name', 'employee:id,name']);
            }

            $journal = new TeachingJournal();
            $journal->institution_id = $institutionId;
            $journal->semester_id = $schedule->semester_id;
            $journal->lesson_schedule_id = $schedule->id;
            $journal->class_id = $schedule->class_id;
            $journal->subject_id = $schedule->subject_id;
            $journal->employee_id = $schedule->employee_id;
            $journal->journal_date = $date;
            $journal->period = $schedule->period;
            $journal->material_taught = null;
            $journal->attendance_notes = null;
            $journal->notes = null;
            $journal->save();

            return $journal->load(['schoolClass:id,name', 'subject:id,name', 'employee:id,name']);
        });
    }

    /**
     * @param  Collection<int, LessonSchedule>|null  $block
     * @return array<string, mixed>
     */
    protected function scheduleSummary(LessonSchedule $schedule, ?Collection $block = null): array
    {
        $periods = $block
            ? $block->pluck('period')->map(fn ($p) => (int) $p)->values()->all()
            : [(int) $schedule->period];
        $ids = $block
            ? $block->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
            : [(int) $schedule->id];

        $periodLabel = count($periods) > 1
            ? 'Jam ke-' . $periods[0] . '–' . $periods[count($periods) - 1]
            : 'Jam ke-' . $periods[0];

        return [
            'id' => $schedule->id,
            'ids' => $ids,
            'semester_id' => $schedule->semester_id,
            'class_id' => $schedule->class_id,
            'subject_id' => $schedule->subject_id,
            'employee_id' => $schedule->employee_id,
            'day_of_week' => $schedule->day_of_week,
            'day_name' => LessonSchedule::getDayName((int) $schedule->day_of_week),
            'period' => (int) $schedule->period,
            'periods' => $periods,
            'period_label' => $periodLabel,
            'is_block' => count($periods) > 1,
            'class_name' => $schedule->schoolClass?->name,
            'subject_name' => $schedule->subject?->name,
            'employee_name' => $schedule->employee?->name,
        ];
    }

    /**
     * List attendances for a teaching journal (one per student in that class).
     */
    public function listByTeachingJournal(int $teachingJournalId, int $institutionId): Collection
    {
        $journal = TeachingJournal::forInstitution($institutionId)->findOrFail($teachingJournalId);
        $existing = StudentAttendance::forTeachingJournal($teachingJournalId)
            ->with('student:id,nis,nisn,name,gender')
            ->get()
            ->keyBy('student_id');

        $students = Student::where('class_id', $journal->class_id)
            ->where('institution_id', $institutionId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'nis', 'nisn', 'name', 'gender']);

        $result = $students->map(function (Student $student) use ($existing) {
            $att = $existing->get($student->id);
            return [
                'student_id' => $student->id,
                'student' => [
                    'id' => $student->id,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                    'name' => $student->name,
                    'gender' => $student->gender,
                ],
                'attendance_id' => $att?->id,
                'status' => $att?->status ?? 'hadir',
                'notes' => $att?->notes,
            ];
        });

        return $result;
    }

    /**
     * History of attendances for a single student (for portal siswa).
     *
     * Returns simple rows per sesi jurnal mengajar dengan informasi tanggal,
     * kelas, mapel, guru, jam ke, status, dan catatan.
     */
    public function historyForStudent(
        int $studentId,
        int $institutionId,
        ?int $semesterId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): Collection {
        $query = StudentAttendance::query()
            ->forInstitution($institutionId)
            ->forStudent($studentId)
            ->with([
                'teachingJournal' => function ($q) use ($semesterId, $dateFrom, $dateTo) {
                    $q->with([
                        'schoolClass:id,name',
                        'subject:id,name',
                        'employee:id,name',
                        'semester:id,name',
                    ]);

                    if ($semesterId) {
                        $q->forSemester($semesterId);
                    }
                    if ($dateFrom) {
                        $q->dateFrom($dateFrom);
                    }
                    if ($dateTo) {
                        $q->dateTo($dateTo);
                    }
                },
            ]);

        // Pastikan hanya sesi yang memiliki jurnal mengajar yang valid
        $query->whereHas('teachingJournal', function ($q) use ($semesterId, $dateFrom, $dateTo) {
            if ($semesterId) {
                $q->forSemester($semesterId);
            }
            if ($dateFrom) {
                $q->dateFrom($dateFrom);
            }
            if ($dateTo) {
                $q->dateTo($dateTo);
            }
        });

        $items = $query
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        return $items->map(function (StudentAttendance $attendance) {
            $journal = $attendance->teachingJournal;

            return [
                'id' => $attendance->id,
                'date' => optional($journal?->journal_date)->format('Y-m-d'),
                'status' => $attendance->status,
                'status_label' => StudentAttendance::STATUSES[$attendance->status] ?? $attendance->status,
                'notes' => $attendance->notes,
                'class_name' => $journal?->schoolClass?->name,
                'subject_name' => $journal?->subject?->name,
                'teacher_name' => $journal?->employee?->name,
                'period' => $journal?->period,
                'semester_name' => $journal?->semester?->name,
            ];
        });
    }

    /**
     * Upsert attendances for a teaching journal (bulk).
     * Validates that each student belongs to the journal's class.
     */
    public function upsertForJournal(int $teachingJournalId, int $institutionId, array $attendances): Collection
    {
        $journal = TeachingJournal::forInstitution($institutionId)->findOrFail($teachingJournalId);
        $studentIdsInClass = Student::where('class_id', $journal->class_id)
            ->where('institution_id', $institutionId)
            ->active()
            ->pluck('id')
            ->flip();

        return DB::transaction(function () use ($journal, $institutionId, $attendances, $studentIdsInClass) {
            $saved = collect();
            foreach ($attendances as $row) {
                $studentId = (int) ($row['student_id'] ?? 0);
                if (!$studentIdsInClass->has($studentId)) {
                    continue;
                }
                $status = $row['status'] ?? 'hadir';
                $notes = $row['notes'] ?? null;

                $att = StudentAttendance::updateOrCreate(
                    [
                        'teaching_journal_id' => $journal->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'institution_id' => $institutionId,
                        'status' => $status,
                        'notes' => $notes,
                    ]
                );
                $att->load('student:id,nis,nisn,name,gender');
                $saved->push($att);
            }
            return $saved;
        });
    }

    /**
     * Update a single student attendance record.
     */
    public function update(StudentAttendance $attendance, array $data): StudentAttendance
    {
        $attendance->update($data);
        return $attendance->fresh(['student:id,nis,nisn,name,gender']);
    }

    /**
     * Aggregate student attendance rekap for reports.
     *
     * Sesi/JP = jumlah jurnal (satu jurnal ≈ satu jam pelajaran).
     * Pertemuan = jumlah hari unik (journal_date) dalam filter.
     *
     * @param  array{semester_id?:int|string,class_id?:int|string,subject_id?:int|string,date_from?:string,date_to?:string}  $filters
     * @return array{rows: array<int, array>, totals: array<string, int|float>, meta: array}
     */
    public function buildRekap(int $institutionId, array $filters = [], ?int $scopeEmployeeId = null): array
    {
        $statusKeys = array_keys(StudentAttendance::STATUSES);

        $journalQuery = TeachingJournal::query()->forInstitution($institutionId);
        if (!empty($filters['semester_id'])) {
            $journalQuery->forSemester((int) $filters['semester_id']);
        }
        if (!empty($filters['class_id'])) {
            $journalQuery->forClass((int) $filters['class_id']);
        }
        if (!empty($filters['subject_id'])) {
            $journalQuery->where('subject_id', (int) $filters['subject_id']);
        }
        if (!empty($filters['date_from'])) {
            $journalQuery->dateFrom($filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $journalQuery->dateTo($filters['date_to']);
        }
        if ($scopeEmployeeId) {
            $journalQuery->forEmployee($scopeEmployeeId);
        }

        $journals = $journalQuery->get(['id', 'class_id', 'journal_date', 'period']);
        $journalIds = $journals->pluck('id');

        $normalizeDate = static function ($date): ?string {
            if ($date === null || $date === '') {
                return null;
            }
            if ($date instanceof \DateTimeInterface) {
                return $date->format('Y-m-d');
            }

            return substr((string) $date, 0, 10) ?: null;
        };

        $jpByClass = $journals->groupBy('class_id')->map->count();
        $meetingsByClass = $journals->groupBy('class_id')->map(function ($group) use ($normalizeDate) {
            return $group->map(fn ($j) => $normalizeDate($j->journal_date))
                ->filter()
                ->unique()
                ->count();
        });

        $jpTotal = $journals->count();
        $meetingDaysTotal = $journals->map(fn ($j) => $normalizeDate($j->journal_date))
            ->filter()
            ->unique()
            ->count();

        $studentsQuery = Student::query()
            ->where('institution_id', $institutionId)
            ->active()
            ->orderBy('name');

        if (!empty($filters['class_id'])) {
            $studentsQuery->where('class_id', (int) $filters['class_id']);
        } elseif ($journals->isNotEmpty()) {
            $studentsQuery->whereIn('class_id', $journals->pluck('class_id')->unique()->filter()->values());
        } else {
            $studentsQuery->whereRaw('1 = 0');
        }

        $students = $studentsQuery->with('class:id,name')->get(['id', 'nis', 'nisn', 'name', 'class_id']);

        $attendanceRows = $journalIds->isEmpty()
            ? collect()
            : StudentAttendance::query()
                ->whereIn('teaching_journal_id', $journalIds)
                ->whereIn('student_id', $students->pluck('id'))
                ->get(['student_id', 'status']);

        $byStudent = $attendanceRows->groupBy('student_id');

        $totals = array_fill_keys($statusKeys, 0);
        $totals['tercatat'] = 0;
        $totals['sesi_diharapkan'] = 0;
        $totals['jp_diharapkan'] = 0;
        $totals['pertemuan_diharapkan'] = 0;

        $rows = $students->map(function (Student $student) use ($byStudent, $jpByClass, $meetingsByClass, $statusKeys, &$totals) {
            $atts = $byStudent->get($student->id, collect());
            $counts = array_fill_keys($statusKeys, 0);
            foreach ($atts as $att) {
                $status = $att->status;
                if (isset($counts[$status])) {
                    $counts[$status]++;
                }
            }
            $tercatat = (int) $atts->count();
            $jpExpected = (int) ($jpByClass[$student->class_id] ?? 0);
            $meetingExpected = (int) ($meetingsByClass[$student->class_id] ?? 0);
            $hadir = $counts['hadir'] ?? 0;
            $persenTercatat = $tercatat > 0 ? round(($hadir / $tercatat) * 100, 1) : 0.0;
            $persenJp = $jpExpected > 0 ? round(($hadir / $jpExpected) * 100, 1) : 0.0;

            foreach ($statusKeys as $key) {
                $totals[$key] += $counts[$key];
            }
            $totals['tercatat'] += $tercatat;
            $totals['sesi_diharapkan'] += $jpExpected;
            $totals['jp_diharapkan'] += $jpExpected;
            $totals['pertemuan_diharapkan'] += $meetingExpected;

            return [
                'student_id' => $student->id,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'name' => $student->name,
                'class_id' => $student->class_id,
                'class_name' => $student->class?->name,
                'counts' => $counts,
                'tercatat' => $tercatat,
                'sesi_diharapkan' => $jpExpected,
                'jp_diharapkan' => $jpExpected,
                'pertemuan_diharapkan' => $meetingExpected,
                'persentase_hadir' => $persenTercatat,
                'persentase_hadir_jp' => $persenJp,
            ];
        })->values()->all();

        $totals['persentase_hadir'] = $totals['tercatat'] > 0
            ? round(($totals['hadir'] / $totals['tercatat']) * 100, 1)
            : 0.0;
        $totals['persentase_hadir_jp'] = $totals['jp_diharapkan'] > 0
            ? round(($totals['hadir'] / $totals['jp_diharapkan']) * 100, 1)
            : 0.0;

        $className = null;
        if (!empty($filters['class_id'])) {
            $className = SchoolClass::where('id', (int) $filters['class_id'])->value('name');
        }
        $semesterName = null;
        if (!empty($filters['semester_id'])) {
            $semesterName = Semester::where('id', (int) $filters['semester_id'])->value('name');
        }
        $subjectName = null;
        if (!empty($filters['subject_id'])) {
            $subjectName = Subject::where('id', (int) $filters['subject_id'])->value('name');
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
            'meta' => [
                'session_count' => $jpTotal,
                'jp_count' => $jpTotal,
                'meeting_days' => $meetingDaysTotal,
                'student_count' => count($rows),
                'class_name' => $className,
                'semester_name' => $semesterName,
                'subject_name' => $subjectName,
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
                'status_labels' => StudentAttendance::STATUSES,
            ],
        ];
    }

    /**
     * Resolve PDF signatory for attendance rekap.
     * - Mapel filter: Guru Mata Pelajaran (from schedule / journal)
     * - Kelas only: Wali Kelas
     * - Otherwise: null → template falls back to kepala sekolah
     *
     * @param  array{semester_id?:int|string,class_id?:int|string,subject_id?:int|string}  $filters
     * @return array{role: string, name: ?string, nip: ?string}|null
     */
    public function resolveRekapSigner(int $institutionId, array $filters = [], ?int $scopeEmployeeId = null): ?array
    {
        $subjectId = !empty($filters['subject_id']) ? (int) $filters['subject_id'] : null;
        $classId = !empty($filters['class_id']) ? (int) $filters['class_id'] : null;
        $semesterId = !empty($filters['semester_id']) ? (int) $filters['semester_id'] : null;

        if ($subjectId) {
            $employee = null;
            if ($scopeEmployeeId) {
                $employee = Employee::query()
                    ->where('id', $scopeEmployeeId)
                    ->where('institution_id', $institutionId)
                    ->first(['id', 'name', 'nip']);
            }

            if (!$employee) {
                $scheduleQuery = LessonSchedule::query()
                    ->where('institution_id', $institutionId)
                    ->where('subject_id', $subjectId)
                    ->whereNotNull('employee_id');
                if ($classId) {
                    $scheduleQuery->where('class_id', $classId);
                }
                if ($semesterId) {
                    $scheduleQuery->where('semester_id', $semesterId);
                }
                $employeeId = $scheduleQuery->value('employee_id');

                if (!$employeeId) {
                    $journalQuery = TeachingJournal::query()
                        ->where('institution_id', $institutionId)
                        ->where('subject_id', $subjectId)
                        ->whereNotNull('employee_id');
                    if ($classId) {
                        $journalQuery->where('class_id', $classId);
                    }
                    if ($semesterId) {
                        $journalQuery->where('semester_id', $semesterId);
                    }
                    $employeeId = $journalQuery->value('employee_id');
                }

                if ($employeeId) {
                    $employee = Employee::query()
                        ->where('id', (int) $employeeId)
                        ->where('institution_id', $institutionId)
                        ->first(['id', 'name', 'nip']);
                }
            }

            if ($employee) {
                return [
                    'role' => 'Guru Mata Pelajaran',
                    'name' => $employee->name,
                    'nip' => $employee->nip,
                ];
            }

            return [
                'role' => 'Guru Mata Pelajaran',
                'name' => null,
                'nip' => null,
            ];
        }

        if ($classId) {
            $class = SchoolClass::query()
                ->with(['teacher:id,name,nip'])
                ->where('id', $classId)
                ->where('institution_id', $institutionId)
                ->first();
            $wali = $class?->teacher;

            return [
                'role' => 'Wali Kelas',
                'name' => $wali?->name,
                'nip' => $wali?->nip,
            ];
        }

        return null;
    }
}

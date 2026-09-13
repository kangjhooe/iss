<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\LessonSchedule;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\TeachingJournal;
use App\Services\GradeService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TeacherTodaySessionService
{
    /**
     * Today's teaching sessions for a teacher, grouped into consecutive blocks
     * when same class+subject+room and contiguous periods.
     *
     * @return array{
     *   date: string,
     *   day_of_week: int,
     *   day_name: string,
     *   sessions: array<int, array>
     * }
     */
    public function forTeacher(
        int $institutionId,
        int $employeeId,
        int $semesterId,
        ?string $date = null
    ): array {
        $day = $date ? Carbon::parse($date) : Carbon::today();
        $dateStr = $day->toDateString();
        $dayOfWeek = (int) $day->dayOfWeekIso;

        $schedules = LessonSchedule::query()
            ->with([
                'schoolClass:id,name,grade',
                'subject:id,name,code',
                'room:id,name,code',
            ])
            ->where('institution_id', $institutionId)
            ->where('semester_id', $semesterId)
            ->where('employee_id', $employeeId)
            ->orderBy('day_of_week')
            ->orderBy('period')
            ->get();

        $blocks = $this->groupIntoBlocks($schedules);
        $sessions = [];

        foreach ($blocks as $block) {
            /** @var Collection<int, LessonSchedule> $block */
            $first = $block->first();
            $scheduleIds = $block->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
            $periods = $block->pluck('period')->map(fn ($p) => (int) $p)->values()->all();

            $journals = TeachingJournal::query()
                ->where('institution_id', $institutionId)
                ->where('employee_id', $employeeId)
                ->whereDate('journal_date', $dateStr)
                ->where(function ($q) use ($scheduleIds, $first, $periods) {
                    $q->whereIn('lesson_schedule_id', $scheduleIds)
                        ->orWhere(function ($q2) use ($first, $periods) {
                            $q2->where('class_id', $first->class_id)
                                ->where('subject_id', $first->subject_id)
                                ->whereIn('period', $periods);
                        });
                })
                ->get();

            $journalFilled = $journals->contains(function (TeachingJournal $j) {
                return filled(trim((string) ($j->material_taught ?? '')));
            });

            $primaryJournal = $journals->sortBy('period')->first();
            $attendanceFilled = false;
            $attendanceCount = 0;
            $studentCount = Student::query()
                ->where('class_id', $first->class_id)
                ->where('institution_id', $institutionId)
                ->count();

            if ($primaryJournal) {
                $attendanceCount = StudentAttendance::query()
                    ->where('teaching_journal_id', $primaryJournal->id)
                    ->count();
                $attendanceFilled = $studentCount > 0 && $attendanceCount >= $studentCount;
            }

            $dailyGradeFilled = false;
            $meeting = $this->meetingContext(
                $institutionId,
                $semesterId,
                (int) $first->class_id,
                (int) $first->subject_id,
                $employeeId,
                $dateStr,
                $primaryJournal
            );
            $penilaianIndex = $meeting['penilaian_index'];

            if ($penilaianIndex >= 1) {
                $dailyGradeFilled = Grade::query()
                    ->where('institution_id', $institutionId)
                    ->where('class_id', $first->class_id)
                    ->where('subject_id', $first->subject_id)
                    ->where('semester_id', $semesterId)
                    ->where('grade_type', Grade::penilaianType($penilaianIndex))
                    ->exists();
            } elseif ($journals->isNotEmpty()) {
                // Fallback lama: nilai penilaian yang diperbarui pada tanggal pertemuan.
                $dailyGradeFilled = Grade::query()
                    ->where('institution_id', $institutionId)
                    ->where('class_id', $first->class_id)
                    ->where('subject_id', $first->subject_id)
                    ->where('semester_id', $semesterId)
                    ->where('grade_type', 'like', 'penilaian_%')
                    ->whereDate('updated_at', $dateStr)
                    ->exists();
            }

            $startTime = $block->min('start_time');
            $endTime = $block->max('end_time');

            $scheduledDay = (int) $first->day_of_week;
            $isOnSchedule = $scheduledDay === $dayOfWeek;

            $sessions[] = [
                'key' => implode('-', $scheduleIds),
                'lesson_schedule_ids' => $scheduleIds,
                'semester_id' => $semesterId,
                'class_id' => (int) $first->class_id,
                'class_name' => $first->schoolClass?->name,
                'subject_id' => (int) $first->subject_id,
                'subject_name' => $first->subject?->name,
                'subject_code' => $first->subject?->code,
                'room_id' => $first->room_id ? (int) $first->room_id : null,
                'room_name' => $first->room?->name,
                'scheduled_day_of_week' => $scheduledDay,
                'scheduled_day_name' => LessonSchedule::DAYS[$scheduledDay] ?? '',
                'is_on_schedule' => $isOnSchedule,
                'periods' => $periods,
                'period_label' => $this->periodLabel($periods),
                'is_block' => count($periods) > 1,
                'start_time' => $this->formatTime($startTime),
                'end_time' => $this->formatTime($endTime),
                'teaching_journal_id' => $primaryJournal?->id,
                'journal_ids' => $journals->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'meeting_number' => $meeting['meeting_number'],
                'total_meetings_recorded' => $meeting['total_meetings_recorded'],
                'penilaian_index' => $meeting['penilaian_index'],
                'suggested_penilaian_index' => $meeting['suggested_penilaian_index'],
                'assessment_count' => $meeting['assessment_count'],
                'status' => [
                    'attendance_filled' => $attendanceFilled,
                    'attendance_count' => $attendanceCount,
                    'student_count' => $studentCount,
                    'journal_filled' => $journalFilled,
                    'daily_grade_filled' => $dailyGradeFilled,
                    'complete' => $attendanceFilled && $journalFilled,
                ],
            ];
        }

        usort($sessions, function (array $a, array $b): int {
            if ($a['is_on_schedule'] !== $b['is_on_schedule']) {
                return $a['is_on_schedule'] ? -1 : 1;
            }
            $dayCmp = ($a['scheduled_day_of_week'] ?? 0) <=> ($b['scheduled_day_of_week'] ?? 0);
            if ($dayCmp !== 0) {
                return $dayCmp;
            }
            $periodA = $a['periods'][0] ?? 0;
            $periodB = $b['periods'][0] ?? 0;

            return $periodA <=> $periodB;
        });

        return [
            'date' => $dateStr,
            'day_of_week' => $dayOfWeek,
            'day_name' => LessonSchedule::DAYS[$dayOfWeek] ?? '',
            'sessions' => $sessions,
            'summary' => [
                'total' => count($sessions),
                'attendance_done' => count(array_filter($sessions, fn ($s) => $s['status']['attendance_filled'])),
                'journal_done' => count(array_filter($sessions, fn ($s) => $s['status']['journal_filled'])),
                'complete' => count(array_filter($sessions, fn ($s) => $s['status']['complete'])),
            ],
        ];
    }

    /**
     * Data cetak lembar jurnal mengajar (satu sesi atau semua sesi tanggal itu).
     *
     * @return array{
     *   date: string,
     *   day_name: string,
     *   teacher: ?Employee,
     *   institution: ?Institution,
     *   semester_name: ?string,
     *   sessions: array<int, array>
     * }
     */
    public function printDocuments(
        int $institutionId,
        int $employeeId,
        int $semesterId,
        ?string $date = null,
        ?string $sessionKey = null,
        ?int $penilaianIndexOverride = null
    ): array {
        $payload = $this->forTeacher($institutionId, $employeeId, $semesterId, $date);
        $sessions = $payload['sessions'];

        if ($sessionKey !== null && $sessionKey !== '') {
            $sessions = array_values(array_filter(
                $sessions,
                fn (array $s) => ($s['key'] ?? '') === $sessionKey
            ));
        }

        $documents = [];
        foreach ($sessions as $session) {
            $documents[] = $this->buildSessionDocument(
                $institutionId,
                $semesterId,
                $payload['date'],
                $session,
                $penilaianIndexOverride
            );
        }

        return [
            'date' => $payload['date'],
            'day_name' => $payload['day_name'],
            'teacher' => Employee::query()->find($employeeId),
            'institution' => Institution::query()->find($institutionId),
            'semester_name' => Semester::query()->where('id', $semesterId)->value('name'),
            'sessions' => $documents,
        ];
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>
     */
    private function buildSessionDocument(
        int $institutionId,
        int $semesterId,
        string $dateStr,
        array $session,
        ?int $penilaianIndexOverride = null
    ): array {
        $journalIds = array_values(array_filter(array_map('intval', $session['journal_ids'] ?? [])));
        $journals = $journalIds === []
            ? collect()
            : TeachingJournal::query()->whereIn('id', $journalIds)->orderBy('period')->get();

        $primaryJournal = $journals->sortBy('period')->first();
        $penilaianIndex = $penilaianIndexOverride;
        if ($penilaianIndex === null || $penilaianIndex < 1) {
            $fromJournal = $primaryJournal?->penilaian_index;
            $penilaianIndex = $fromJournal ? (int) $fromJournal : null;
        }

        $material = $journals
            ->map(fn (TeachingJournal $j) => trim((string) ($j->material_taught ?? '')))
            ->filter()
            ->unique()
            ->implode("\n\n");
        $attendanceNotes = $journals
            ->map(fn (TeachingJournal $j) => trim((string) ($j->attendance_notes ?? '')))
            ->filter()
            ->unique()
            ->implode('; ');
        $notes = $journals
            ->map(fn (TeachingJournal $j) => trim((string) ($j->notes ?? '')))
            ->filter()
            ->unique()
            ->implode("\n");

        $primaryJournalId = (int) ($session['teaching_journal_id'] ?? 0);
        $attendances = $primaryJournalId > 0
            ? StudentAttendance::query()->where('teaching_journal_id', $primaryJournalId)->get()->keyBy('student_id')
            : collect();

        $students = Student::query()
            ->where('class_id', (int) $session['class_id'])
            ->where('institution_id', $institutionId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'nis', 'name']);

        $gradeData = $this->resolveSessionGrades(
            $institutionId,
            $semesterId,
            (int) $session['class_id'],
            (int) $session['subject_id'],
            $dateStr,
            $penilaianIndex
        );
        $gradeColumns = $gradeData['grade_columns'];
        $gradesByStudent = $gradeData['grades_by_student'];
        $dailyGradeFilled = $gradeData['daily_grade_filled'];

        $nilaiAkhirByStudent = Grade::query()
            ->where('institution_id', $institutionId)
            ->where('class_id', (int) $session['class_id'])
            ->where('subject_id', (int) $session['subject_id'])
            ->where('semester_id', $semesterId)
            ->where('grade_type', Grade::TYPE_NILAI_AKHIR)
            ->get()
            ->keyBy('student_id');

        $statusCounts = [
            StudentAttendance::STATUS_HADIR => 0,
            StudentAttendance::STATUS_ALPHA => 0,
            StudentAttendance::STATUS_IZIN => 0,
            StudentAttendance::STATUS_SAKIT => 0,
            StudentAttendance::STATUS_DINAS_LUAR => 0,
        ];
        $recorded = 0;

        $rows = $students->map(function (Student $student) use ($attendances, $gradesByStudent, $nilaiAkhirByStudent, &$statusCounts, &$recorded) {
            $att = $attendances->get($student->id);
            $status = $att?->status;
            if ($status) {
                $recorded++;
                if (isset($statusCounts[$status])) {
                    $statusCounts[$status]++;
                }
            }

            return [
                'nis' => $student->nis,
                'name' => $student->name,
                'status' => $status,
                'status_label' => $status
                    ? (StudentAttendance::STATUSES[$status] ?? $status)
                    : '—',
                'attendance_notes' => $att?->notes,
                'grades' => $gradesByStudent[(int) $student->id] ?? [],
                'nilai_akhir' => $nilaiAkhirByStudent->get($student->id)?->value,
            ];
        })->all();

        return [
            'class_name' => $session['class_name'] ?? null,
            'subject_name' => $session['subject_name'] ?? null,
            'room_name' => $session['room_name'] ?? null,
            'period_label' => $session['period_label'] ?? null,
            'start_time' => $session['start_time'] ?? null,
            'end_time' => $session['end_time'] ?? null,
            'journal' => [
                'filled' => (bool) ($session['status']['journal_filled'] ?? false),
                'material_taught' => $material,
                'attendance_notes' => $attendanceNotes,
                'notes' => $notes,
            ],
            'attendance' => [
                'recorded' => $recorded,
                'student_count' => count($rows),
                'counts' => $statusCounts,
                'filled' => (bool) ($session['status']['attendance_filled'] ?? false),
            ],
            'grade_columns' => $gradeColumns,
            'daily_grade_filled' => $dailyGradeFilled,
            'students' => $rows,
        ];
    }

    /**
     * @return array{
     *   grade_columns: array<int, int>,
     *   grades_by_student: array<int, array<int, mixed>>,
     *   daily_grade_filled: bool
     * }
     */
    private function resolveSessionGrades(
        int $institutionId,
        int $semesterId,
        int $classId,
        int $subjectId,
        string $dateStr,
        ?int $penilaianIndex
    ): array {
        if ($penilaianIndex !== null && $penilaianIndex >= 1) {
            $grades = Grade::query()
                ->where('institution_id', $institutionId)
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('semester_id', $semesterId)
                ->where('grade_type', Grade::penilaianType($penilaianIndex))
                ->get();

            $gradesByStudent = [];
            foreach ($grades as $grade) {
                $gradesByStudent[(int) $grade->student_id][$penilaianIndex] = $grade->value;
            }

            return [
                'grade_columns' => [$penilaianIndex],
                'grades_by_student' => $gradesByStudent,
                'daily_grade_filled' => $grades->isNotEmpty(),
            ];
        }

        $gradesToday = Grade::query()
            ->where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->where('grade_type', 'like', 'penilaian_%')
            ->whereDate('updated_at', $dateStr)
            ->get();

        $gradeColumns = $gradesToday
            ->map(fn (Grade $g) => Grade::penilaianIndex((string) $g->grade_type))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $gradesByStudent = [];
        foreach ($gradesToday as $grade) {
            $idx = Grade::penilaianIndex((string) $grade->grade_type);
            if ($idx === null) {
                continue;
            }
            $gradesByStudent[(int) $grade->student_id][$idx] = $grade->value;
        }

        return [
            'grade_columns' => $gradeColumns,
            'grades_by_student' => $gradesByStudent,
            'daily_grade_filled' => $gradesToday->isNotEmpty(),
        ];
    }

    /**
     * @param  Collection<int, LessonSchedule>  $schedules
     * @return array<int, Collection<int, LessonSchedule>>
     */
    private function groupIntoBlocks(Collection $schedules): array
    {
        $blocks = [];
        $current = collect();

        foreach ($schedules as $schedule) {
            if ($current->isEmpty()) {
                $current->push($schedule);

                continue;
            }

            $prev = $current->last();
            $sameClassSubject = (int) $prev->class_id === (int) $schedule->class_id
                && (int) $prev->subject_id === (int) $schedule->subject_id
                && (int) $prev->day_of_week === (int) $schedule->day_of_week;
            $contiguous = ((int) $schedule->period) === ((int) $prev->period + 1);

            if ($sameClassSubject && $contiguous) {
                $current->push($schedule);
            } else {
                $blocks[] = $current;
                $current = collect([$schedule]);
            }
        }

        if ($current->isNotEmpty()) {
            $blocks[] = $current;
        }

        return $blocks;
    }

    /**
     * @param  array<int, int>  $periods
     */
    private function periodLabel(array $periods): string
    {
        if ($periods === []) {
            return '';
        }
        if (count($periods) === 1) {
            return 'Jam ke-'.$periods[0];
        }

        return 'Jam ke-'.$periods[0].'–'.$periods[count($periods) - 1];
    }

    private function formatTime(mixed $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        $str = (string) $time;
        if (preg_match('/^(\d{1,2}:\d{2})/', $str, $m)) {
            return $m[1];
        }

        return substr($str, 0, 5) ?: null;
    }

    /**
     * Konteks pertemuan: urutan pertemuan mengajar dan kolom penilaian (P1, P2, …).
     *
     * @return array{
     *   meeting_number: int,
     *   total_meetings_recorded: int,
     *   penilaian_index: int,
     *   suggested_penilaian_index: int,
     *   assessment_count: int
     * }
     */
    private function meetingContext(
        int $institutionId,
        int $semesterId,
        int $classId,
        int $subjectId,
        int $employeeId,
        string $dateStr,
        ?TeachingJournal $primaryJournal
    ): array {
        $dateKeys = TeachingJournal::query()
            ->where('institution_id', $institutionId)
            ->where('semester_id', $semesterId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('employee_id', $employeeId)
            ->orderBy('journal_date')
            ->pluck('journal_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values();

        if (! $dateKeys->contains($dateStr)) {
            $dateKeys = $dateKeys->push($dateStr)->sort()->values();
        }

        $meetingFromDates = max(1, (int) $dateKeys->search($dateStr) + 1);

        $penilaianFromJournal = $primaryJournal?->penilaian_index
            ? (int) $primaryJournal->penilaian_index
            : null;

        // Journal kosong dari absensi sudah "exists", tapi belum punya P —
        // anggap tanggal ini belum ter-assign kolom nilai.
        $needsNextPenilaian = $penilaianFromJournal === null;

        $gradeService = app(GradeService::class);
        $maxPenilaianFromJournals = (int) (TeachingJournal::query()
            ->where('institution_id', $institutionId)
            ->where('semester_id', $semesterId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('employee_id', $employeeId)
            ->whereNotNull('penilaian_index')
            ->max('penilaian_index') ?? 0);
        $maxPenilaianFromGrades = $gradeService->maxPenilaianIndex($institutionId, $classId, $subjectId, $semesterId);
        $maxUsedPenilaian = max($maxPenilaianFromJournals, $maxPenilaianFromGrades);

        if ($penilaianFromJournal !== null) {
            $suggestedPenilaian = $penilaianFromJournal;
        } else {
            // Pertemuan baru (belum punya penilaian_index): naik ke P berikutnya.
            $nextFromPrior = $maxUsedPenilaian > 0 && $needsNextPenilaian
                ? $maxUsedPenilaian + 1
                : max($maxUsedPenilaian, 1);
            $suggestedPenilaian = max($meetingFromDates, $nextFromPrior, 1);
        }

        $weights = $gradeService->resolveWeights($institutionId, $classId, $subjectId, $semesterId);
        $assessmentCount = max(
            (int) $weights['assessment_count'],
            $maxPenilaianFromGrades,
            $maxUsedPenilaian,
            $suggestedPenilaian,
            $meetingFromDates,
            1
        );

        return [
            'meeting_number' => $meetingFromDates,
            'total_meetings_recorded' => $dateKeys->count(),
            'penilaian_index' => $penilaianFromJournal ?? $suggestedPenilaian,
            'suggested_penilaian_index' => $suggestedPenilaian,
            'assessment_count' => $assessmentCount,
        ];
    }
}

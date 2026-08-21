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
            ->where('day_of_week', $dayOfWeek)
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
            if ($journals->isNotEmpty()) {
                // Nilai harian dianggap terisi jika ada penilaian_* yang diupdate hari ini
                // atau ada nilai penilaian di kelas+mapel+semester (indikator soft).
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
                'periods' => $periods,
                'period_label' => $this->periodLabel($periods),
                'is_block' => count($periods) > 1,
                'start_time' => $this->formatTime($startTime),
                'end_time' => $this->formatTime($endTime),
                'teaching_journal_id' => $primaryJournal?->id,
                'journal_ids' => $journals->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
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
        ?string $sessionKey = null
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
                $session
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
        array $session
    ): array {
        $journalIds = array_values(array_filter(array_map('intval', $session['journal_ids'] ?? [])));
        $journals = $journalIds === []
            ? collect()
            : TeachingJournal::query()->whereIn('id', $journalIds)->orderBy('period')->get();

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

        $gradesToday = Grade::query()
            ->where('institution_id', $institutionId)
            ->where('class_id', (int) $session['class_id'])
            ->where('subject_id', (int) $session['subject_id'])
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
            'daily_grade_filled' => (bool) ($session['status']['daily_grade_filled'] ?? false),
            'students' => $rows,
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
                && (int) $prev->subject_id === (int) $schedule->subject_id;
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
}

<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\LessonSchedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class LessonScheduleExportService
{
    public function __construct(
        protected LessonScheduleService $lessonScheduleService,
        protected LessonScheduleTemplateService $templateService
    ) {}

    /**
     * @param  array{mode: string, semester_id: int, class_id?: int, employee_id?: int, subject_id?: int}  $params
     */
    public function exportPdf(int $institutionId, array $params): Response
    {
        $mode = $params['mode'];
        $semesterId = (int) $params['semester_id'];

        $institution = Institution::findOrFail($institutionId);
        $semester = Semester::with('academicYear:id,name')
            ->where('id', $semesterId)
            ->firstOrFail();

        $payload = match ($mode) {
            'class' => $this->buildClassPayload($institutionId, $semesterId, (int) $params['class_id']),
            'teacher' => $this->buildTeacherPayload($institutionId, $semesterId, (int) $params['employee_id']),
            'subject' => $this->buildSubjectPayload($institutionId, $semesterId, (int) $params['subject_id']),
            default => throw new \InvalidArgumentException('Mode cetak tidak valid.'),
        };

        $view = $mode === 'subject'
            ? 'lesson_schedule.print_subject'
            : 'lesson_schedule.print_matrix';

        $wakaKurikulum = $this->resolveWakaKurikulum($institutionId);

        $pdf = DomPDF::loadView($view, [
            'institution' => $institution,
            'semester' => $semester,
            'printed_at' => now()->locale('id')->translatedFormat('d F Y H:i'),
            'waka_kurikulum' => $wakaKurikulum,
            ...$payload,
        ])->setPaper('a4', 'landscape');

        $filename = $this->filename($mode, $payload, $semester);

        return $pdf->stream($filename, ['Attachment' => false]);
    }

    /**
     * @return array{title: string, subtitle: string, active_days: array, max_periods: int, grid: array, meta_lines: array}
     */
    private function buildClassPayload(int $institutionId, int $semesterId, int $classId): array
    {
        $class = SchoolClass::where('institution_id', $institutionId)->findOrFail($classId);
        $data = $this->lessonScheduleService->getByClass($classId, $semesterId, $institutionId);

        $activeDays = array_values(array_filter(
            $data['matrix'] ?? [],
            fn ($d) => empty($d['is_holiday']) && (int) ($d['periods'] ?? 0) > 0
        ));
        $maxPeriods = (int) ($data['template']['max_periods'] ?? 0);

        $grid = [];
        for ($period = 1; $period <= $maxPeriods; $period++) {
            $row = [];
            foreach ($activeDays as $day) {
                $dayOfWeek = (int) $day['day_of_week'];
                if ($period > (int) $day['periods']) {
                    $row[$dayOfWeek] = ['out' => true, 'lines' => []];
                    continue;
                }
                $slot = $day['slots'][$period] ?? null;
                $slotArr = $slot ? (is_array($slot) ? $slot : $slot->resolve()) : null;
                $row[$dayOfWeek] = [
                    'out' => false,
                    'lines' => $slotArr ? array_values(array_filter([
                        $slotArr['subject']['name'] ?? null,
                        $slotArr['employee']['name'] ?? null,
                        ! empty($slotArr['room']['name']) ? $slotArr['room']['name'] : null,
                    ])) : [],
                ];
            }
            $grid[$period] = $row;
        }

        return [
            'title' => 'JADWAL PELAJARAN',
            'subtitle' => 'Kelas '.$class->name,
            'meta_lines' => [
                'Kelas: '.$class->name,
            ],
            'active_days' => $activeDays,
            'max_periods' => $maxPeriods,
            'grid' => $grid,
            'entity_label' => $class->name,
        ];
    }

    /**
     * @return array{title: string, subtitle: string, active_days: array, max_periods: int, grid: array, meta_lines: array}
     */
    private function buildTeacherPayload(int $institutionId, int $semesterId, int $employeeId): array
    {
        $employee = Employee::forInstitution($institutionId)->where('id', $employeeId)->firstOrFail();
        $schedules = $this->lessonScheduleService->getByTeacher($employeeId, $semesterId, $institutionId);

        // Envelope: max JP/day across all class templates the teacher teaches
        // (multi-template schools) plus any occupied slot as a safety floor.
        $maxByDay = array_fill_keys(array_keys(LessonSchedule::DAYS), 0);
        $classIds = $schedules->pluck('class_id')->unique()->filter()->values();

        foreach ($classIds as $classId) {
            $template = $this->templateService->getForClass($institutionId, $semesterId, (int) $classId);
            foreach ($this->templateService->maxPeriodsByDay($template) as $day => $max) {
                $day = (int) $day;
                $maxByDay[$day] = max($maxByDay[$day] ?? 0, (int) $max);
            }
        }

        foreach ($schedules as $s) {
            $day = (int) $s->day_of_week;
            $maxByDay[$day] = max($maxByDay[$day] ?? 0, (int) $s->period);
        }

        if ($classIds->isEmpty()) {
            $fallback = $this->templateService->getOrDefault($institutionId, $semesterId);
            foreach ($this->templateService->maxPeriodsByDay($fallback) as $day => $max) {
                $day = (int) $day;
                $maxByDay[$day] = max($maxByDay[$day] ?? 0, (int) $max);
            }
        }

        $days = [];
        foreach (LessonSchedule::DAYS as $day => $name) {
            $periods = (int) ($maxByDay[$day] ?? 0);
            $days[] = [
                'day_of_week' => $day,
                'day_name' => $name,
                'periods' => $periods,
                'is_holiday' => $periods <= 0,
            ];
        }

        $activeDays = array_values(array_filter(
            $days,
            fn ($d) => empty($d['is_holiday']) && (int) ($d['periods'] ?? 0) > 0
        ));
        $maxPeriods = collect($days)->max('periods') ?: 0;

        $byKey = $schedules->keyBy(fn ($s) => ((int) $s->day_of_week).'-'.((int) $s->period));

        $grid = [];
        for ($period = 1; $period <= $maxPeriods; $period++) {
            $row = [];
            foreach ($activeDays as $day) {
                $dayOfWeek = (int) $day['day_of_week'];
                if ($period > (int) $day['periods']) {
                    $row[$dayOfWeek] = ['out' => true, 'lines' => []];
                    continue;
                }
                $slot = $byKey->get($dayOfWeek.'-'.$period);
                $row[$dayOfWeek] = [
                    'out' => false,
                    'lines' => $slot ? array_values(array_filter([
                        $slot->subject?->name,
                        $slot->schoolClass?->name,
                        $slot->room?->name,
                    ])) : [],
                ];
            }
            $grid[$period] = $row;
        }

        return [
            'title' => 'JADWAL MENGAJAR',
            'subtitle' => $employee->name,
            'meta_lines' => array_values(array_filter([
                'Guru: '.$employee->name,
                $employee->nip ? 'NIP: '.$employee->nip : null,
            ])),
            'active_days' => $activeDays,
            'max_periods' => $maxPeriods,
            'grid' => $grid,
            'entity_label' => $employee->name,
        ];
    }

    /**
     * @return array{title: string, subtitle: string, rows: Collection, meta_lines: array, entity_label: string}
     */
    private function buildSubjectPayload(int $institutionId, int $semesterId, int $subjectId): array
    {
        $subject = Subject::where('institution_id', $institutionId)->findOrFail($subjectId);

        $schedules = LessonSchedule::with([
            'schoolClass:id,name',
            'employee:id,name,nip',
            'room:id,name',
        ])
            ->forInstitution($institutionId)
            ->forSemester($semesterId)
            ->where('subject_id', $subjectId)
            ->orderBy('class_id')
            ->orderBy('day_of_week')
            ->orderBy('period')
            ->get();

        $rows = $schedules->map(fn (LessonSchedule $s) => [
            'class_name' => $s->schoolClass?->name ?? '-',
            'day_name' => LessonSchedule::getDayName((int) $s->day_of_week),
            'period' => $s->period,
            'teacher_name' => $s->employee?->name ?? '-',
            'room_name' => $s->room?->name ?? '—',
        ]);

        return [
            'title' => 'JADWAL MATA PELAJARAN',
            'subtitle' => ($subject->code ? $subject->code.' — ' : '').$subject->name,
            'meta_lines' => [
                'Mata Pelajaran: '.(($subject->code ? $subject->code.' — ' : '').$subject->name),
            ],
            'rows' => $rows,
            'entity_label' => $subject->name,
        ];
    }

    private function filename(string $mode, array $payload, Semester $semester): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($payload['entity_label'] ?? $mode));
        $sem = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $semester->name);

        return match ($mode) {
            'class' => "Jadwal_Kelas_{$safe}_{$sem}.pdf",
            'teacher' => "Jadwal_Guru_{$safe}_{$sem}.pdf",
            'subject' => "Jadwal_Mapel_{$safe}_{$sem}.pdf",
            default => "Jadwal_{$sem}.pdf",
        };
    }

    /**
     * Pegawai aktif dengan tugas tambahan waka_kurikulum di institusi ini.
     */
    private function resolveWakaKurikulum(int $institutionId): ?Employee
    {
        return Employee::forInstitution($institutionId)
            ->whereHas('additionalDuties', function ($query) {
                $query->where('additional_duties.key', 'waka_kurikulum')
                    ->where(function ($active) {
                        $active->whereNull('employee_additional_duties.ended_at')
                            ->orWhere('employee_additional_duties.ended_at', '>', now());
                    });
            })
            ->orderBy('name')
            ->first(['id', 'name', 'nip']);
    }
}

<?php

namespace App\Services;

use App\Models\LessonSchedule;
use App\Models\LessonScheduleTemplate;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Employee;
use App\Models\Semester;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LessonScheduleService
{
    public function __construct(
        protected LessonScheduleTemplateService $templateService
    ) {}

    /**
     * List lesson schedules with filters.
     */
    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = LessonSchedule::with([
            'semester:id,name,academic_year_id',
            'schoolClass:id,name,grade',
            'subject:id,code,name',
            'employee:id,name,nip',
            'room:id,name,code',
        ])
            ->forInstitution($institutionId)
            ->orderBy('semester_id')
            ->orderBy('class_id')
            ->orderBy('day_of_week')
            ->orderBy('period');

        if (!empty($filters['semester_id'])) {
            $query->forSemester($filters['semester_id']);
        }
        if (!empty($filters['class_id'])) {
            $query->forClass($filters['class_id']);
        }
        if (!empty($filters['employee_id'])) {
            $query->forEmployee($filters['employee_id']);
        }
        if (isset($filters['day_of_week']) && $filters['day_of_week'] !== '') {
            $query->where('day_of_week', $filters['day_of_week']);
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get schedule by class (matrix: day x period) shaped by template.
     */
    public function getByClass(int $classId, int $semesterId, int $institutionId): array
    {
        $schedules = LessonSchedule::with(['subject:id,code,name', 'employee:id,name', 'room:id,name'])
            ->forInstitution($institutionId)
            ->forSemester($semesterId)
            ->forClass($classId)
            ->orderBy('day_of_week')
            ->orderBy('period')
            ->get();

        $class = SchoolClass::query()
            ->where('id', $classId)
            ->where('institution_id', $institutionId)
            ->first();

        $template = $this->templateService->getForClass($institutionId, $semesterId, $classId);
        $days = LessonScheduleTemplate::normalizeDays($template->days ?? []);

        $matrix = [];
        foreach ($days as $dayRow) {
            $day = (int) $dayRow['day_of_week'];
            $periods = (int) $dayRow['periods'];
            $isHoliday = (bool) $dayRow['is_holiday'];
            $slots = [];
            if (! $isHoliday && $periods > 0) {
                for ($period = 1; $period <= $periods; $period++) {
                    $slot = $schedules->first(
                        fn ($s) => (int) $s->day_of_week === $day && (int) $s->period === $period
                    );
                    $slots[$period] = $slot ? new \App\Http\Resources\LessonScheduleResource($slot) : null;
                }
            }
            $matrix[$day] = [
                'day_of_week' => $day,
                'day_name' => $dayRow['day_name'],
                'periods' => $periods,
                'is_holiday' => $isHoliday,
                'slots' => $slots,
            ];
        }

        return [
            'class_id' => $classId,
            'semester_id' => $semesterId,
            'lesson_schedule_template_id' => $class?->lesson_schedule_template_id
                ? (int) $class->lesson_schedule_template_id
                : null,
            'template' => [
                'id' => $template->exists ? (int) $template->id : null,
                'name' => $template->name ?: 'Sementara',
                'days' => $days,
                'max_periods' => collect($days)->max('periods') ?: 0,
                'is_persisted' => (bool) $template->exists,
            ],
            'matrix' => array_values($matrix),
            'schedules' => \App\Http\Resources\LessonScheduleResource::collection($schedules),
        ];
    }

    /**
     * Get schedule by teacher (employee).
     */
    public function getByTeacher(int $employeeId, int $semesterId, int $institutionId): Collection
    {
        return LessonSchedule::with(['schoolClass:id,name', 'subject:id,code,name', 'room:id,name'])
            ->forInstitution($institutionId)
            ->forSemester($semesterId)
            ->forEmployee($employeeId)
            ->orderBy('day_of_week')
            ->orderBy('period')
            ->get();
    }

    /**
     * Get schedule by room.
     */
    public function getByRoom(int $roomId, int $semesterId, int $institutionId): Collection
    {
        return LessonSchedule::with(['schoolClass:id,name', 'subject:id,code,name', 'employee:id,name'])
            ->forInstitution($institutionId)
            ->forSemester($semesterId)
            ->forRoom($roomId)
            ->orderBy('day_of_week')
            ->orderBy('period')
            ->get();
    }

    /**
     * Unique class/subject pairs + lists for a teacher in one semester (teaching load).
     *
     * @return array{classes: array, subjects: array, pairs: array, schedules: Collection}
     */
    public function getTeachingLoad(int $employeeId, int $semesterId, int $institutionId): array
    {
        $schedules = $this->getByTeacher($employeeId, $semesterId, $institutionId);

        $classes = [];
        $subjects = [];
        $pairs = [];
        $seenClass = [];
        $seenSubject = [];
        $seenPair = [];

        foreach ($schedules as $s) {
            if (!isset($seenClass[$s->class_id])) {
                $seenClass[$s->class_id] = true;
                $classes[] = [
                    'id' => $s->class_id,
                    'name' => $s->schoolClass?->name,
                ];
            }
            if (!isset($seenSubject[$s->subject_id])) {
                $seenSubject[$s->subject_id] = true;
                $subjects[] = [
                    'id' => $s->subject_id,
                    'code' => $s->subject?->code,
                    'name' => $s->subject?->name,
                ];
            }
            $pairKey = $s->class_id . '-' . $s->subject_id;
            if (!isset($seenPair[$pairKey])) {
                $seenPair[$pairKey] = true;
                $pairs[] = [
                    'class_id' => $s->class_id,
                    'class_name' => $s->schoolClass?->name,
                    'subject_id' => $s->subject_id,
                    'subject_name' => $s->subject?->name,
                    'semester_id' => $semesterId,
                ];
            }
        }

        usort($classes, fn ($a, $b) => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
        usort($subjects, fn ($a, $b) => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
        usort($pairs, fn ($a, $b) => strcmp(
            ($a['class_name'] ?? '') . ($a['subject_name'] ?? ''),
            ($b['class_name'] ?? '') . ($b['subject_name'] ?? '')
        ));

        return [
            'classes' => $classes,
            'subjects' => $subjects,
            'pairs' => $pairs,
            'schedules' => $schedules,
        ];
    }

    /**
     * Whether teacher teaches class+subject in semester at institution.
     */
    public function teacherTeaches(int $employeeId, int $semesterId, int $classId, int $subjectId, int $institutionId): bool
    {
        return LessonSchedule::query()
            ->where('employee_id', $employeeId)
            ->where('semester_id', $semesterId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('institution_id', $institutionId)
            ->exists();
    }

    /**
     * Find best matching schedule slot for journal linking.
     */
    public function findMatchingSchedule(
        int $institutionId,
        int $semesterId,
        int $classId,
        int $subjectId,
        int $employeeId,
        ?int $period = null
    ): ?LessonSchedule {
        $query = LessonSchedule::query()
            ->where('institution_id', $institutionId)
            ->where('semester_id', $semesterId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('employee_id', $employeeId);

        if ($period !== null) {
            $withPeriod = (clone $query)->where('period', $period)->first();
            if ($withPeriod) {
                return $withPeriod;
            }
        }

        return $query->orderBy('day_of_week')->orderBy('period')->first();
    }

    /**
     * Create lesson schedule with conflict checks.
     */
    public function create(int $institutionId, array $data): LessonSchedule
    {
        $allowTeacherConflict = ! empty($data['allow_teacher_conflict']);
        unset($data['allow_teacher_conflict']);

        $this->validateBelongsToInstitution($institutionId, $data);
        $this->assertWithinTemplate(
            (int) $data['semester_id'],
            $institutionId,
            (int) $data['day_of_week'],
            (int) $data['period'],
            (int) $data['class_id']
        );
        $this->purgeTrashedSlot(
            (int) $data['semester_id'],
            (int) $data['class_id'],
            (int) $data['day_of_week'],
            (int) $data['period']
        );
        $this->assertSlotUnique($data['semester_id'], $data['class_id'], $data['day_of_week'], $data['period'], null);
        if (! $allowTeacherConflict) {
            $this->assertTeacherAvailable(
                (int) $data['employee_id'],
                (int) $data['semester_id'],
                (int) $data['day_of_week'],
                (int) $data['period'],
                null
            );
        }
        if (!empty($data['room_id'])) {
            $this->assertRoomAvailable(
                $institutionId,
                (int) $data['room_id'],
                (int) $data['semester_id'],
                (int) $data['day_of_week'],
                (int) $data['period'],
                null
            );
        }

        $data['institution_id'] = $institutionId;
        $schedule = LessonSchedule::create($data);

        return $schedule->load(['semester', 'schoolClass', 'subject', 'employee', 'room']);
    }

    /**
     * Create consecutive periods (block) for the same subject/teacher.
     *
     * @return Collection<int, LessonSchedule>
     */
    public function createBlock(int $institutionId, array $data, int $duration = 1): Collection
    {
        $duration = max(1, min(10, $duration));
        $startPeriod = (int) $data['period'];
        $dayOfWeek = (int) $data['day_of_week'];
        $semesterId = (int) $data['semester_id'];

        $classId = (int) $data['class_id'];
        $template = $this->templateService->getForClass($institutionId, $semesterId, $classId);
        $maxForDay = $template->periodsForDay($dayOfWeek);
        if ($maxForDay <= 0) {
            throw new \InvalidArgumentException('Hari yang dipilih adalah hari libur menurut template jadwal.');
        }
        if ($startPeriod + $duration - 1 > $maxForDay) {
            throw new \InvalidArgumentException(
                "Durasi {$duration} JP melebihi sisa jam pada hari ini (maks jam ke-{$maxForDay})."
            );
        }

        return DB::transaction(function () use ($institutionId, $data, $duration, $startPeriod) {
            $created = collect();
            for ($i = 0; $i < $duration; $i++) {
                $slotData = $data;
                $slotData['period'] = $startPeriod + $i;
                $created->push($this->create($institutionId, $slotData));
            }

            return $created;
        });
    }

    /**
     * Update lesson schedule with conflict checks.
     */
    public function update(LessonSchedule $schedule, array $data): LessonSchedule
    {
        $allowTeacherConflict = ! empty($data['allow_teacher_conflict']);
        unset($data['allow_teacher_conflict']);

        $semesterId = (int) ($data['semester_id'] ?? $schedule->semester_id);
        $classId = (int) ($data['class_id'] ?? $schedule->class_id);
        $dayOfWeek = (int) ($data['day_of_week'] ?? $schedule->day_of_week);
        $period = (int) ($data['period'] ?? $schedule->period);
        $employeeId = (int) ($data['employee_id'] ?? $schedule->employee_id);
        $roomId = array_key_exists('room_id', $data) ? $data['room_id'] : $schedule->room_id;

        $this->purgeTrashedSlot($semesterId, $classId, $dayOfWeek, $period, $schedule->id);
        $this->assertSlotUnique($semesterId, $classId, $dayOfWeek, $period, $schedule->id);
        $this->assertWithinTemplate($semesterId, (int) $schedule->institution_id, $dayOfWeek, $period, $classId);
        if (! $allowTeacherConflict) {
            $this->assertTeacherAvailable($employeeId, $semesterId, $dayOfWeek, $period, $schedule->id);
        }
        if (!empty($roomId)) {
            $this->assertRoomAvailable(
                (int) $schedule->institution_id,
                (int) $roomId,
                $semesterId,
                $dayOfWeek,
                $period,
                $schedule->id
            );
        }

        if (!empty($data)) {
            $this->validateBelongsToInstitution($schedule->institution_id, $data);
            $schedule->update($data);
        }

        return $schedule->fresh(['semester', 'schoolClass', 'subject', 'employee', 'room']);
    }

    /**
     * Copy schedules from source semester to target (optionally for one class).
     * Target class is matched by same class name in target semester.
     */
    public function copySemester(int $institutionId, int $sourceSemesterId, int $targetSemesterId, ?int $classId = null): int
    {
        return (int) DB::transaction(function () use ($institutionId, $sourceSemesterId, $targetSemesterId, $classId) {
            $this->copyClassTemplateAssignments($institutionId, $sourceSemesterId, $targetSemesterId, $classId);

            $query = LessonSchedule::with('schoolClass:id,name')
                ->forInstitution($institutionId)
                ->forSemester($sourceSemesterId);

            if ($classId !== null) {
                $query->forClass($classId);
            }

            $sourceSchedules = $query->get();
            $targetClassesByName = SchoolClass::where('institution_id', $institutionId)
                ->where('semester_id', $targetSemesterId)
                ->get()
                ->keyBy('name');

            $count = 0;
            foreach ($sourceSchedules as $s) {
                $sourceClassName = $s->schoolClass->name ?? null;
                if (!$sourceClassName) {
                    continue;
                }
                $targetClass = $targetClassesByName->get($sourceClassName);
                if (!$targetClass) {
                    continue;
                }

                $exists = LessonSchedule::forSemester($targetSemesterId)
                    ->forClass($targetClass->id)
                    ->where('day_of_week', $s->day_of_week)
                    ->where('period', $s->period)
                    ->exists();

                if (!$exists) {
                    try {
                        $this->assertTeacherAvailable(
                            (int) $s->employee_id,
                            $targetSemesterId,
                            (int) $s->day_of_week,
                            (int) $s->period,
                            null
                        );
                        if (!empty($s->room_id)) {
                            $this->assertRoomAvailable(
                                $institutionId,
                                (int) $s->room_id,
                                $targetSemesterId,
                                (int) $s->day_of_week,
                                (int) $s->period,
                                null
                            );
                        }
                    } catch (\InvalidArgumentException) {
                        continue;
                    }

                    $this->purgeTrashedSlot(
                        $targetSemesterId,
                        (int) $targetClass->id,
                        (int) $s->day_of_week,
                        (int) $s->period
                    );

                    LessonSchedule::create([
                        'institution_id' => $institutionId,
                        'semester_id' => $targetSemesterId,
                        'class_id' => $targetClass->id,
                        'subject_id' => $s->subject_id,
                        'employee_id' => $s->employee_id,
                        'room_id' => $s->room_id,
                        'day_of_week' => $s->day_of_week,
                        'period' => $s->period,
                        'start_time' => $s->start_time,
                        'end_time' => $s->end_time,
                        'notes' => $s->notes,
                    ]);
                    $count++;
                }
            }

            return $count;
        });
    }

    /**
     * Copy template assignment from source-semester classes to target-semester
     * classes with the same name (templates remapped by name, created if missing).
     */
    private function copyClassTemplateAssignments(
        int $institutionId,
        int $sourceSemesterId,
        int $targetSemesterId,
        ?int $classId = null
    ): void {
        $sourceClassesQuery = SchoolClass::query()
            ->where('institution_id', $institutionId)
            ->where('semester_id', $sourceSemesterId)
            ->whereNotNull('lesson_schedule_template_id');

        if ($classId !== null) {
            $sourceClassesQuery->where('id', $classId);
        }

        $sourceClasses = $sourceClassesQuery->get(['id', 'name', 'lesson_schedule_template_id']);
        if ($sourceClasses->isEmpty()) {
            return;
        }

        $sourceTemplates = LessonScheduleTemplate::query()
            ->where('institution_id', $institutionId)
            ->where('semester_id', $sourceSemesterId)
            ->whereIn('id', $sourceClasses->pluck('lesson_schedule_template_id')->unique()->filter())
            ->get()
            ->keyBy('id');

        $targetTemplatesByName = LessonScheduleTemplate::query()
            ->where('institution_id', $institutionId)
            ->where('semester_id', $targetSemesterId)
            ->get()
            ->keyBy('name');

        $targetClassesByName = SchoolClass::query()
            ->where('institution_id', $institutionId)
            ->where('semester_id', $targetSemesterId)
            ->get()
            ->keyBy('name');

        foreach ($sourceClasses as $sourceClass) {
            $targetClass = $targetClassesByName->get($sourceClass->name);
            if (! $targetClass) {
                continue;
            }

            $sourceTemplate = $sourceTemplates->get($sourceClass->lesson_schedule_template_id);
            if (! $sourceTemplate) {
                continue;
            }

            $targetTemplate = $targetTemplatesByName->get($sourceTemplate->name);
            if (! $targetTemplate) {
                $targetTemplate = $this->templateService->create(
                    $institutionId,
                    $targetSemesterId,
                    $sourceTemplate->name,
                    LessonScheduleTemplate::normalizeDays($sourceTemplate->days ?? [])
                );
                $targetTemplatesByName->put($targetTemplate->name, $targetTemplate);
            }

            if ((int) $targetClass->lesson_schedule_template_id !== (int) $targetTemplate->id) {
                $targetClass->lesson_schedule_template_id = $targetTemplate->id;
                $targetClass->save();
            }
        }
    }

    /**
     * Delete all schedules for a class in a semester.
     */
    public function deleteByClass(int $classId, int $semesterId, int $institutionId): int
    {
        return LessonSchedule::forInstitution($institutionId)
            ->forSemester($semesterId)
            ->forClass($classId)
            ->delete();
    }

    /**
     * Delete all schedules for a semester.
     */
    public function deleteBySemester(int $semesterId, int $institutionId): int
    {
        return LessonSchedule::forInstitution($institutionId)
            ->forSemester($semesterId)
            ->delete();
    }

    private function validateBelongsToInstitution(int $institutionId, array $data): void
    {
        if (isset($data['semester_id'])) {
            Semester::findOrFail($data['semester_id']);
        }
        if (isset($data['class_id'])) {
            SchoolClass::where('id', $data['class_id'])->where('institution_id', $institutionId)->firstOrFail();
        }
        if (isset($data['subject_id'])) {
            Subject::where('id', $data['subject_id'])->where('institution_id', $institutionId)->firstOrFail();
        }
        if (isset($data['employee_id'])) {
            Employee::forInstitution($institutionId)->where('id', $data['employee_id'])->firstOrFail();
        }
        if (!empty($data['room_id'])) {
            \App\Models\Room::where('id', $data['room_id'])->where('institution_id', $institutionId)->firstOrFail();
        }
    }

    private function assertWithinTemplate(
        int $semesterId,
        int $institutionId,
        int $dayOfWeek,
        int $period,
        ?int $classId = null
    ): void {
        $template = $classId
            ? $this->templateService->getForClass($institutionId, $semesterId, $classId)
            : $this->templateService->getOrDefault($institutionId, $semesterId);
        $maxForDay = $template->periodsForDay($dayOfWeek);
        if ($maxForDay <= 0) {
            throw new \InvalidArgumentException('Hari yang dipilih adalah hari libur menurut template jadwal.');
        }
        if ($period < 1 || $period > $maxForDay) {
            throw new \InvalidArgumentException("Jam ke-{$period} di luar template (maks {$maxForDay} JP).");
        }
    }

    private function assertSlotUnique(int $semesterId, int $classId, int $dayOfWeek, int $period, ?int $excludeId): void
    {
        $query = LessonSchedule::where('semester_id', $semesterId)
            ->where('class_id', $classId)
            ->where('day_of_week', $dayOfWeek)
            ->where('period', $period);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new \InvalidArgumentException('Slot jadwal untuk kelas, hari, dan jam ke ini sudah terisi.');
        }
    }

    /**
     * Soft-deleted rows still occupy the unique index; remove them before insert/update.
     */
    private function purgeTrashedSlot(
        int $semesterId,
        int $classId,
        int $dayOfWeek,
        int $period,
        ?int $excludeId = null
    ): void {
        $query = LessonSchedule::onlyTrashed()
            ->where('semester_id', $semesterId)
            ->where('class_id', $classId)
            ->where('day_of_week', $dayOfWeek)
            ->where('period', $period);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $query->forceDelete();
    }

    /**
     * Guru tidak boleh bentrok jam yang sama di sekolah manapun (multi-institusi)
     * pada semester yang sama. Semester lalu tidak memblokir semester aktif.
     */
    private function assertTeacherAvailable(
        int $employeeId,
        int $semesterId,
        int $dayOfWeek,
        int $period,
        ?int $excludeId
    ): void {
        $query = LessonSchedule::with(['institution:id,name', 'schoolClass:id,name'])
            ->where('employee_id', $employeeId)
            ->where('semester_id', $semesterId)
            ->where('day_of_week', $dayOfWeek)
            ->where('period', $period);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $conflict = $query->first();
        if ($conflict) {
            $dayName = LessonSchedule::getDayName($dayOfWeek);
            $school = $conflict->institution?->name ?? 'sekolah lain';
            $className = $conflict->schoolClass?->name ?? '-';
            throw new \InvalidArgumentException(
                "Guru sudah terjadwal pada {$dayName} jam ke-{$period} di {$school} (kelas {$className})."
            );
        }
    }

    /**
     * Ruangan tidak boleh bentrok pada hari+jam yang sama di institusi & semester yang sama.
     */
    private function assertRoomAvailable(
        int $institutionId,
        int $roomId,
        int $semesterId,
        int $dayOfWeek,
        int $period,
        ?int $excludeId
    ): void {
        $query = LessonSchedule::with(['schoolClass:id,name'])
            ->where('institution_id', $institutionId)
            ->where('semester_id', $semesterId)
            ->where('room_id', $roomId)
            ->where('day_of_week', $dayOfWeek)
            ->where('period', $period);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $conflict = $query->first();
        if ($conflict) {
            $dayName = LessonSchedule::getDayName($dayOfWeek);
            $className = $conflict->schoolClass?->name ?? '-';
            throw new \InvalidArgumentException(
                "Ruangan sudah dipakai pada {$dayName} jam ke-{$period} (kelas {$className})."
            );
        }
    }
}

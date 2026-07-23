<?php

namespace App\Services;

use App\Models\LessonSchedule;
use App\Models\LessonScheduleTemplate;
use App\Models\SchoolClass;
use App\Models\Semester;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LessonScheduleTemplateService
{
    /**
     * @return Collection<int, LessonScheduleTemplate>
     */
    public function listForSemester(int $institutionId, int $semesterId): Collection
    {
        Semester::findOrFail($semesterId);

        return LessonScheduleTemplate::query()
            ->where('institution_id', $institutionId)
            ->where('semester_id', $semesterId)
            ->orderBy('name')
            ->get()
            ->each(function (LessonScheduleTemplate $template) {
                $template->days = LessonScheduleTemplate::normalizeDays($template->days ?? []);
            });
    }

    /**
     * Fallback template for institution+semester when class has none assigned.
     * Prefers an explicitly assigned/first saved template; otherwise a virtual unsaved template.
     */
    public function getOrDefault(int $institutionId, int $semesterId): LessonScheduleTemplate
    {
        Semester::findOrFail($semesterId);

        $template = LessonScheduleTemplate::query()
            ->where('institution_id', $institutionId)
            ->where('semester_id', $semesterId)
            ->orderBy('name')
            ->orderBy('id')
            ->first();

        if ($template) {
            $template->days = LessonScheduleTemplate::normalizeDays($template->days ?? []);

            return $template;
        }

        $template = new LessonScheduleTemplate([
            'institution_id' => $institutionId,
            'semester_id' => $semesterId,
            'name' => 'Sementara',
            'days' => LessonScheduleTemplate::defaultDays(),
        ]);
        $template->exists = false;

        return $template;
    }

    /**
     * Template assigned to a class, or semester fallback when none assigned.
     */
    public function getForClass(int $institutionId, int $semesterId, int $classId): LessonScheduleTemplate
    {
        $class = SchoolClass::query()
            ->where('id', $classId)
            ->where('institution_id', $institutionId)
            ->first();

        if ($class?->lesson_schedule_template_id) {
            $assigned = LessonScheduleTemplate::query()
                ->where('id', $class->lesson_schedule_template_id)
                ->where('institution_id', $institutionId)
                ->where('semester_id', $semesterId)
                ->first();

            if ($assigned) {
                $assigned->days = LessonScheduleTemplate::normalizeDays($assigned->days ?? []);

                return $assigned;
            }
        }

        return $this->getOrDefault($institutionId, $semesterId);
    }

    public function findForInstitution(int $institutionId, int $templateId): LessonScheduleTemplate
    {
        return LessonScheduleTemplate::query()
            ->where('institution_id', $institutionId)
            ->where('id', $templateId)
            ->firstOrFail();
    }

    public function create(int $institutionId, int $semesterId, string $name, array $days): LessonScheduleTemplate
    {
        Semester::findOrFail($semesterId);
        $normalized = LessonScheduleTemplate::normalizeDays($days);
        $name = trim($name) !== '' ? trim($name) : 'Template';

        return LessonScheduleTemplate::create([
            'institution_id' => $institutionId,
            'semester_id' => $semesterId,
            'name' => $name,
            'days' => $normalized,
        ]);
    }

    public function update(LessonScheduleTemplate $template, array $data): LessonScheduleTemplate
    {
        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);
            $template->name = $name !== '' ? $name : $template->name;
        }

        if (array_key_exists('days', $data) && is_array($data['days'])) {
            $template->days = LessonScheduleTemplate::normalizeDays($data['days']);
        }

        $template->save();

        return $template->fresh();
    }

    public function delete(LessonScheduleTemplate $template): void
    {
        DB::transaction(function () use ($template) {
            SchoolClass::query()
                ->where('lesson_schedule_template_id', $template->id)
                ->update(['lesson_schedule_template_id' => null]);

            $template->delete();
        });
    }

    /**
     * Max JP per day for a template (0 = holiday / no teaching).
     *
     * @return array<int, int>
     */
    public function maxPeriodsByDay(LessonScheduleTemplate $template): array
    {
        $maxByDay = [];
        foreach (LessonScheduleTemplate::normalizeDays($template->days ?? []) as $dayRow) {
            $day = (int) $dayRow['day_of_week'];
            $isHoliday = ! empty($dayRow['is_holiday']) || (int) ($dayRow['periods'] ?? 0) <= 0;
            $maxByDay[$day] = $isHoliday ? 0 : (int) $dayRow['periods'];
        }

        return $maxByDay;
    }

    /**
     * Schedules for a class that would fall outside the given template shape.
     *
     * @return Collection<int, LessonSchedule>
     */
    public function findOutOfBoundSchedules(
        int $institutionId,
        int $classId,
        int $semesterId,
        LessonScheduleTemplate $template
    ): Collection {
        $maxByDay = $this->maxPeriodsByDay($template);

        return LessonSchedule::query()
            ->with(['subject:id,code,name', 'employee:id,name'])
            ->where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('semester_id', $semesterId)
            ->orderBy('day_of_week')
            ->orderBy('period')
            ->get()
            ->filter(function (LessonSchedule $schedule) use ($maxByDay) {
                $day = (int) $schedule->day_of_week;
                $max = $maxByDay[$day] ?? 0;

                return $max <= 0 || (int) $schedule->period > $max;
            })
            ->values();
    }

    /**
     * Preview impact of assigning a template to a class.
     *
     * @return array{
     *   class_id: int,
     *   current_template_id: int|null,
     *   target_template_id: int|null,
     *   target_template_name: string|null,
     *   affected_count: int,
     *   requires_prune: bool,
     *   affected_slots: array<int, array<string, mixed>>
     * }
     */
    public function previewAssignToClass(
        int $institutionId,
        int $classId,
        ?int $templateId
    ): array {
        $class = SchoolClass::query()
            ->where('id', $classId)
            ->where('institution_id', $institutionId)
            ->firstOrFail();

        $currentId = $class->lesson_schedule_template_id
            ? (int) $class->lesson_schedule_template_id
            : null;

        if ($templateId === null || $currentId === $templateId) {
            $name = null;
            if ($templateId) {
                $name = $this->findForInstitution($institutionId, $templateId)->name;
            }

            return [
                'class_id' => $class->id,
                'current_template_id' => $currentId,
                'target_template_id' => $templateId,
                'target_template_name' => $name,
                'affected_count' => 0,
                'requires_prune' => false,
                'affected_slots' => [],
            ];
        }

        $template = LessonScheduleTemplate::query()
            ->where('id', $templateId)
            ->where('institution_id', $institutionId)
            ->firstOrFail();

        if ((int) $class->semester_id !== (int) $template->semester_id) {
            throw new \InvalidArgumentException('Template harus dari semester yang sama dengan kelas.');
        }

        $affected = $this->findOutOfBoundSchedules(
            $institutionId,
            $classId,
            (int) $class->semester_id,
            $template
        );

        return [
            'class_id' => $class->id,
            'current_template_id' => $currentId,
            'target_template_id' => $templateId,
            'target_template_name' => $template->name,
            'affected_count' => $affected->count(),
            'requires_prune' => $affected->isNotEmpty(),
            'affected_slots' => $affected->take(10)->map(function (LessonSchedule $s) {
                return [
                    'id' => $s->id,
                    'day_of_week' => (int) $s->day_of_week,
                    'day_name' => LessonSchedule::DAYS[(int) $s->day_of_week] ?? '-',
                    'period' => (int) $s->period,
                    'subject_name' => $s->subject?->name,
                    'teacher_name' => $s->employee?->name,
                ];
            })->values()->all(),
        ];
    }

    /**
     * Assign a template to a class. When switching to a narrower template,
     * out-of-bound slots are pruned if $pruneOutOfBounds is true.
     *
     * @return array{class: SchoolClass, pruned_count: int, requires_prune: bool, affected_count: int, affected_slots?: array}
     */
    public function assignToClass(
        int $institutionId,
        int $classId,
        ?int $templateId,
        bool $pruneOutOfBounds = false
    ): array {
        $class = SchoolClass::query()
            ->where('id', $classId)
            ->where('institution_id', $institutionId)
            ->firstOrFail();

        if ($templateId === null) {
            $class->lesson_schedule_template_id = null;
            $class->save();

            return [
                'class' => $class->fresh(),
                'pruned_count' => 0,
                'requires_prune' => false,
                'affected_count' => 0,
            ];
        }

        $template = LessonScheduleTemplate::query()
            ->where('id', $templateId)
            ->where('institution_id', $institutionId)
            ->firstOrFail();

        if ((int) $class->semester_id !== (int) $template->semester_id) {
            throw new \InvalidArgumentException('Template harus dari semester yang sama dengan kelas.');
        }

        $affected = $this->findOutOfBoundSchedules(
            $institutionId,
            $classId,
            (int) $class->semester_id,
            $template
        );

        if ($affected->isNotEmpty() && ! $pruneOutOfBounds) {
            return [
                'class' => $class,
                'pruned_count' => 0,
                'requires_prune' => true,
                'affected_count' => $affected->count(),
                'affected_slots' => $affected->take(10)->map(function (LessonSchedule $s) {
                    return [
                        'id' => $s->id,
                        'day_of_week' => (int) $s->day_of_week,
                        'day_name' => LessonSchedule::DAYS[(int) $s->day_of_week] ?? '-',
                        'period' => (int) $s->period,
                        'subject_name' => $s->subject?->name,
                        'teacher_name' => $s->employee?->name,
                    ];
                })->values()->all(),
            ];
        }

        return DB::transaction(function () use ($class, $template, $affected) {
            $pruned = 0;
            if ($affected->isNotEmpty()) {
                // forceDelete: soft-deleted rows still occupy the unique slot index
                // (semester_id, class_id, day_of_week, period) and would block recreate.
                $pruned = LessonSchedule::query()
                    ->whereIn('id', $affected->pluck('id'))
                    ->forceDelete();
            }

            $class->lesson_schedule_template_id = $template->id;
            $class->save();

            return [
                'class' => $class->fresh(),
                'pruned_count' => (int) $pruned,
                'requires_prune' => false,
                'affected_count' => $affected->count(),
            ];
        });
    }

    /**
     * @deprecated Prefer create/update. Kept for older clients that upsert by semester.
     */
    public function upsert(int $institutionId, int $semesterId, array $days, ?string $name = null): LessonScheduleTemplate
    {
        $existing = $this->getOrDefault($institutionId, $semesterId);
        if ($existing->exists) {
            return $this->update($existing, [
                'days' => $days,
                'name' => $name ?? $existing->name,
            ]);
        }

        return $this->create(
            $institutionId,
            $semesterId,
            $name ?: 'Template',
            $days
        );
    }
}

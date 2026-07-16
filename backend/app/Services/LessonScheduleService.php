<?php

namespace App\Services;

use App\Models\LessonSchedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Employee;
use App\Models\Semester;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LessonScheduleService
{
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
     * Get schedule by class (matrix: day x period).
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

        $matrix = [];
        foreach (LessonSchedule::DAYS as $day => $dayName) {
            $matrix[$day] = [
                'day_of_week' => $day,
                'day_name' => $dayName,
                'slots' => [],
            ];
            for ($period = 1; $period <= 10; $period++) {
                $slot = $schedules->first(fn ($s) => $s->day_of_week == $day && $s->period == $period);
                $matrix[$day]['slots'][$period] = $slot ? new \App\Http\Resources\LessonScheduleResource($slot) : null;
            }
        }

        return [
            'class_id' => $classId,
            'semester_id' => $semesterId,
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
     * Create lesson schedule with conflict checks.
     */
    public function create(int $institutionId, array $data): LessonSchedule
    {
        $this->validateBelongsToInstitution($institutionId, $data);
        $this->assertSlotUnique($data['semester_id'], $data['class_id'], $data['day_of_week'], $data['period'], null);

        $data['institution_id'] = $institutionId;
        $schedule = LessonSchedule::create($data);

        return $schedule->load(['semester', 'schoolClass', 'subject', 'employee', 'room']);
    }

    /**
     * Update lesson schedule with conflict checks.
     */
    public function update(LessonSchedule $schedule, array $data): LessonSchedule
    {
        if (isset($data['semester_id'], $data['class_id'], $data['day_of_week'], $data['period'])) {
            $this->assertSlotUnique(
                $data['semester_id'],
                $data['class_id'],
                $data['day_of_week'],
                $data['period'],
                $schedule->id
            );
        } else {
            $this->assertSlotUnique(
                $schedule->semester_id,
                $schedule->class_id,
                $data['day_of_week'] ?? $schedule->day_of_week,
                $data['period'] ?? $schedule->period,
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
}

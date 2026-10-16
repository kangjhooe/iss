<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LabSchedule;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LabScheduleService
{
    public function listForRoom(int $institutionId, int $roomId, int $semesterId): Collection
    {
        $this->assertLabRoom($institutionId, $roomId);

        return LabSchedule::with(['schoolClass:id,name', 'subject:id,code,name', 'employee:id,name', 'room:id,name,code'])
            ->where('institution_id', $institutionId)
            ->where('room_id', $roomId)
            ->where('semester_id', $semesterId)
            ->orderBy('day_of_week')
            ->orderBy('period')
            ->get();
    }

    public function create(int $institutionId, array $data): LabSchedule
    {
        $payload = $this->normalizePayload($institutionId, $data);
        $this->assertNoOverlap(
            $institutionId,
            $payload['room_id'],
            $payload['semester_id'],
            $payload['day_of_week'],
            $payload['period'],
            $payload['duration'],
            null
        );

        return LabSchedule::create($payload)->load([
            'schoolClass:id,name',
            'subject:id,code,name',
            'employee:id,name',
            'room:id,name,code',
        ]);
    }

    public function update(LabSchedule $schedule, array $data): LabSchedule
    {
        $payload = $this->normalizePayload((int) $schedule->institution_id, array_merge([
            'room_id' => $schedule->room_id,
            'semester_id' => $schedule->semester_id,
            'class_id' => $schedule->class_id,
            'subject_id' => $schedule->subject_id,
            'employee_id' => $schedule->employee_id,
            'day_of_week' => $schedule->day_of_week,
            'period' => $schedule->period,
            'duration' => $schedule->duration,
            'notes' => $schedule->notes,
        ], $data));

        $this->assertNoOverlap(
            (int) $schedule->institution_id,
            $payload['room_id'],
            $payload['semester_id'],
            $payload['day_of_week'],
            $payload['period'],
            $payload['duration'],
            (int) $schedule->id
        );

        $schedule->update($payload);

        return $schedule->fresh([
            'schoolClass:id,name',
            'subject:id,code,name',
            'employee:id,name',
            'room:id,name,code',
        ]);
    }

    public function delete(LabSchedule $schedule): void
    {
        $schedule->delete();
    }

    private function normalizePayload(int $institutionId, array $data): array
    {
        $roomId = (int) ($data['room_id'] ?? 0);
        $semesterId = (int) ($data['semester_id'] ?? 0);
        $classId = (int) ($data['class_id'] ?? 0);
        $subjectId = (int) ($data['subject_id'] ?? 0);
        $employeeId = (int) ($data['employee_id'] ?? 0);
        $dayOfWeek = (int) ($data['day_of_week'] ?? 0);
        $period = (int) ($data['period'] ?? 0);
        $duration = max(1, min(10, (int) ($data['duration'] ?? 1)));

        $this->assertLabRoom($institutionId, $roomId);

        $semester = Semester::find($semesterId);
        if (! $semester) {
            throw ValidationException::withMessages(['semester_id' => 'Semester tidak valid.']);
        }

        $class = SchoolClass::where('id', $classId)->where('institution_id', $institutionId)->first();
        if (! $class) {
            throw ValidationException::withMessages(['class_id' => 'Kelas tidak valid untuk institusi ini.']);
        }

        $subject = Subject::where('id', $subjectId)->where('institution_id', $institutionId)->first();
        if (! $subject) {
            throw ValidationException::withMessages(['subject_id' => 'Mapel tidak valid untuk institusi ini.']);
        }

        $employee = Employee::where('id', $employeeId)->where('institution_id', $institutionId)->first();
        if (! $employee) {
            // Multi-institution teachers may belong via assignment; fallback existence check
            $employee = Employee::find($employeeId);
            if (! $employee) {
                throw ValidationException::withMessages(['employee_id' => 'Guru tidak valid.']);
            }
        }

        if ($dayOfWeek < 1 || $dayOfWeek > 7) {
            throw ValidationException::withMessages(['day_of_week' => 'Hari tidak valid.']);
        }
        if ($period < 1 || $period > 12) {
            throw ValidationException::withMessages(['period' => 'Jam ke tidak valid.']);
        }
        if ($period + $duration - 1 > 12) {
            throw ValidationException::withMessages(['duration' => 'Durasi melebihi batas jam (maks jam ke-12).']);
        }

        return [
            'institution_id' => $institutionId,
            'room_id' => $roomId,
            'semester_id' => $semesterId,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'employee_id' => $employeeId,
            'day_of_week' => $dayOfWeek,
            'period' => $period,
            'duration' => $duration,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function assertLabRoom(int $institutionId, int $roomId): void
    {
        $room = Room::where('id', $roomId)
            ->where('institution_id', $institutionId)
            ->where('type', 'Laboratorium')
            ->first();

        if (! $room) {
            throw ValidationException::withMessages(['room_id' => 'Ruang lab tidak valid.']);
        }
    }

    private function assertNoOverlap(
        int $institutionId,
        int $roomId,
        int $semesterId,
        int $dayOfWeek,
        int $period,
        int $duration,
        ?int $excludeId
    ): void {
        $newStart = $period;
        $newEnd = $period + $duration - 1;

        $query = LabSchedule::query()
            ->where('institution_id', $institutionId)
            ->where('room_id', $roomId)
            ->where('semester_id', $semesterId)
            ->where('day_of_week', $dayOfWeek);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        foreach ($query->get(['id', 'period', 'duration', 'class_id']) as $existing) {
            $existStart = (int) $existing->period;
            $existEnd = $existStart + max(1, (int) $existing->duration) - 1;
            if ($newStart <= $existEnd && $existStart <= $newEnd) {
                $className = SchoolClass::where('id', $existing->class_id)->value('name') ?? '-';
                $dayName = LabSchedule::getDayName($dayOfWeek);
                throw ValidationException::withMessages([
                    'period' => "Lab sudah terpakai pada {$dayName} jam ke-{$existStart}"
                        . ($existEnd > $existStart ? "–{$existEnd}" : '')
                        . " (kelas {$className}).",
                ]);
            }
        }
    }
}

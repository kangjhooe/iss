<?php

namespace App\Http\Resources;

use App\Models\LabSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var LabSchedule $this */
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'room_id' => $this->room_id,
            'semester_id' => $this->semester_id,
            'class_id' => $this->class_id,
            'subject_id' => $this->subject_id,
            'employee_id' => $this->employee_id,
            'day_of_week' => $this->day_of_week,
            'day_name' => LabSchedule::getDayName((int) $this->day_of_week),
            'period' => $this->period,
            'duration' => $this->duration,
            'period_end' => $this->periodEnd(),
            'period_label' => $this->periodLabel(),
            'notes' => $this->notes,
            'semester' => $this->whenLoaded('semester', fn () => [
                'id' => $this->semester->id,
                'name' => $this->semester->name,
            ]),
            'room' => $this->whenLoaded('room', fn () => $this->room ? [
                'id' => $this->room->id,
                'name' => $this->room->name,
                'code' => $this->room->code,
            ] : null),
            'school_class' => $this->whenLoaded('schoolClass', fn () => [
                'id' => $this->schoolClass->id,
                'name' => $this->schoolClass->name,
            ]),
            'subject' => $this->whenLoaded('subject', fn () => [
                'id' => $this->subject->id,
                'code' => $this->subject->code,
                'name' => $this->subject->name,
            ]),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

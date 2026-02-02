<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'semester_id' => $this->semester_id,
            'class_id' => $this->class_id,
            'subject_id' => $this->subject_id,
            'employee_id' => $this->employee_id,
            'room_id' => $this->room_id,
            'day_of_week' => $this->day_of_week,
            'day_name' => \App\Models\LessonSchedule::getDayName($this->day_of_week),
            'period' => $this->period,
            'start_time' => $this->start_time?->format('H:i'),
            'end_time' => $this->end_time?->format('H:i'),
            'notes' => $this->notes,
            'semester' => $this->whenLoaded('semester', fn () => [
                'id' => $this->semester->id,
                'name' => $this->semester->name,
                'academic_year_id' => $this->semester->academic_year_id,
            ]),
            'school_class' => $this->whenLoaded('schoolClass', fn () => [
                'id' => $this->schoolClass->id,
                'name' => $this->schoolClass->name,
                'grade' => $this->schoolClass->grade,
            ]),
            'subject' => $this->whenLoaded('subject', fn () => [
                'id' => $this->subject->id,
                'code' => $this->subject->code,
                'name' => $this->subject->name,
            ]),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'nip' => $this->employee->nip,
            ]),
            'room' => $this->whenLoaded('room', fn () => $this->room ? [
                'id' => $this->room->id,
                'name' => $this->room->name,
                'code' => $this->room->code,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

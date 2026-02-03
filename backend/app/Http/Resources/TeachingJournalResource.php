<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeachingJournalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'semester_id' => $this->semester_id,
            'lesson_schedule_id' => $this->lesson_schedule_id,
            'class_id' => $this->class_id,
            'subject_id' => $this->subject_id,
            'employee_id' => $this->employee_id,
            'journal_date' => $this->journal_date?->format('Y-m-d'),
            'period' => $this->period,
            'material_taught' => $this->material_taught,
            'attendance_notes' => $this->attendance_notes,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'semester' => $this->whenLoaded('semester', fn () => $this->semester ? [
                'id' => $this->semester->id,
                'name' => $this->semester->name,
            ] : null),
            'school_class' => $this->whenLoaded('schoolClass', fn () => $this->schoolClass ? [
                'id' => $this->schoolClass->id,
                'name' => $this->schoolClass->name,
            ] : null),
            'subject' => $this->whenLoaded('subject', fn () => $this->subject ? [
                'id' => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ] : null),
            'employee' => $this->whenLoaded('employee', fn () => $this->employee ? [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
            ] : null),
            'lesson_schedule' => $this->whenLoaded('lessonSchedule', fn () => $this->lessonSchedule ? [
                'id' => $this->lessonSchedule->id,
                'day_of_week' => $this->lessonSchedule->day_of_week,
                'period' => $this->lessonSchedule->period,
            ] : null),
        ];
    }
}

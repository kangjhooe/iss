<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicCalendarEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'institution_name' => $this->when(
                $this->relationLoaded('institution'),
                fn () => $this->institution?->name
            ),
            'academic_year_id' => $this->academic_year_id,
            'semester_id' => $this->semester_id,
            'title' => $this->title,
            'description' => $this->description,
            'event_type' => $this->event_type,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'is_all_day' => $this->is_all_day,
            'start_time' => $this->start_time ? (is_string($this->start_time) ? substr($this->start_time, 0, 5) : $this->start_time) : null,
            'end_time' => $this->end_time ? (is_string($this->end_time) ? substr($this->end_time, 0, 5) : $this->end_time) : null,
            'reminder_days_before' => $this->reminder_days_before,
            'color' => $this->color,
            'status' => $this->status,
            'reminder_sent_at' => $this->reminder_sent_at?->format('Y-m-d H:i:s'),
            'created_by' => $this->created_by,
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'semester' => new SemesterResource($this->whenLoaded('semester')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

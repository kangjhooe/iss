<?php

namespace App\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtracurricularResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'name' => $this->name,
            'description' => $this->description,
            'supervisor_employee_id' => $this->supervisor_employee_id,
            'supervisor' => $this->whenLoaded('supervisor', function () {
                return [
                    'id' => $this->supervisor->id,
                    'name' => $this->supervisor->name,
                    'nip' => $this->supervisor->nip,
                    'email' => $this->supervisor->email,
                ];
            }),
            'academic_year_id' => $this->academic_year_id,
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
                'code' => $this->academicYear->code,
            ] : null),
            'semester_id' => $this->semester_id,
            'semester' => $this->whenLoaded('semester', fn () => $this->semester ? [
                'id' => $this->semester->id,
                'name' => $this->semester->name,
            ] : null),
            'capacity' => $this->capacity,
            'kkm' => $this->kkm !== null ? (float) $this->kkm : 75.0,
            'status' => $this->status,
            'is_pramuka' => (bool) $this->is_pramuka,
            'assessment_mode' => $this->assessment_mode ?: 'standard',
            'days_of_week' => $this->days_of_week ?? [],
            'day_labels' => $this->day_labels,
            'start_time' => $this->formatTimeValue($this->start_time),
            'end_time' => $this->formatTimeValue($this->end_time),
            'room_id' => $this->room_id,
            'room' => $this->whenLoaded('room', fn () => $this->room ? [
                'id' => $this->room->id,
                'name' => $this->room->name,
                'code' => $this->room->code,
            ] : null),
            'is_outdoor' => (bool) $this->is_outdoor,
            'location_note' => $this->location_note,
            'location_label' => $this->location_label,
            'students_count' => $this->when(isset($this->students_count), $this->students_count),
            'participants_count' => $this->when(isset($this->participants_count), $this->participants_count),
            'participants' => $this->whenLoaded('extracurricularStudents', function () {
                return ExtracurricularStudentResource::collection($this->extracurricularStudents);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function formatTimeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof CarbonInterface) {
            return $value->format('H:i');
        }
        if (is_string($value)) {
            return substr($value, 0, 5);
        }

        return null;
    }
}

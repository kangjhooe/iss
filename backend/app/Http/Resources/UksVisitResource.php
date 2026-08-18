<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UksVisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'student_id' => $this->student_id,
            'recorded_by' => $this->recorded_by,
            'uks_visit_type_id' => $this->uks_visit_type_id,
            'visit_date' => $this->visit_date?->format('Y-m-d'),
            'status' => $this->status,
            'complaint' => $this->complaint,
            'action_taken' => $this->action_taken,
            'notes' => $this->notes,
            'height_cm' => $this->height_cm,
            'weight_kg' => $this->weight_kg,
            'blood_pressure' => $this->blood_pressure,
            'temperature_c' => $this->temperature_c,
            'academic_year_id' => $this->academic_year_id,
            'semester_id' => $this->semester_id,
            'class_id' => $this->class_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'nis' => $this->student->nis,
                'nisn' => $this->student->nisn,
                'class' => $this->student->relationLoaded('class') && $this->student->getRelation('class')
                    ? ['id' => $this->student->class->id, 'name' => $this->student->class->name]
                    : null,
            ]),
            'recorder' => $this->whenLoaded('recorder', fn () => [
                'id' => $this->recorder->id,
                'name' => $this->recorder->name,
                'email' => $this->recorder->email,
            ]),
            'visit_type' => $this->whenLoaded('visitType', fn () => $this->visitType ? [
                'id' => $this->visitType->id,
                'name' => $this->visitType->name,
                'code' => $this->visitType->code,
                'description' => $this->visitType->description,
            ] : null),
        ];
    }
}

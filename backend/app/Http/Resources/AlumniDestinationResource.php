<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlumniDestinationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'name' => $this->student->name,
                    'nisn' => $this->student->nisn,
                    'graduation_year' => $this->student->graduation_year,
                ];
            }),
            'destination_type' => $this->destination_type,
            'destination_type_label' => \App\Models\AlumniDestination::DESTINATION_TYPES[$this->destination_type] ?? $this->destination_type,
            'destination_name' => $this->destination_name,
            'program_or_position' => $this->program_or_position,
            'year_entered' => $this->year_entered,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

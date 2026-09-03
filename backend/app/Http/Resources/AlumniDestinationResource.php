<?php

namespace App\Http\Resources;

use App\Models\AlumniDestination;
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
            'destination_type_label' => AlumniDestination::DESTINATION_TYPES[$this->destination_type] ?? $this->destination_type,
            'destination_name' => $this->destination_name,
            'program_or_position' => $this->program_or_position,
            'year_entered' => $this->year_entered,
            'notes' => $this->notesForDisplay(),
            'status' => $this->status ?: AlumniDestination::STATUS_APPROVED,
            'source' => $this->source ?: AlumniDestination::SOURCE_MANUAL,
            'related_student_id' => $this->related_student_id,
            'related_institution_id' => $this->related_institution_id,
            'related_institution_name' => $this->whenLoaded('relatedInstitution', fn () => $this->relatedInstitution?->name),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'is_pending' => $this->isPending(),
            'is_auto' => $this->isAutoEnrollment(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

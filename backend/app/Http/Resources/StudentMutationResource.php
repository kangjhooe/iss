<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentMutationResource extends JsonResource
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
            'origin_institution_id' => $this->origin_institution_id,
            'origin_npsn' => $this->origin_npsn,
            'origin_school_name' => $this->origin_school_name,
            'target_institution_id' => $this->target_institution_id,
            'target_npsn' => $this->target_npsn,
            'target_school_name' => $this->target_school_name,
            'student_id' => $this->student_id,
            'student_grade' => $this->student_grade,
            'student_gender' => $this->student_gender,
            'initiated_by' => $this->initiated_by,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            // Relations
            'origin_institution' => $this->whenLoaded('originInstitution', fn () => $this->originInstitution ? [
                'id' => $this->originInstitution->id,
                'name' => $this->originInstitution->name,
                'npsn' => $this->originInstitution->npsn,
                'level' => $this->originInstitution->level,
            ] : null),
            'is_external_origin' => $this->origin_institution_id === null,
            'target_institution' => $this->whenLoaded('targetInstitution', fn () => $this->targetInstitution ? [
                'id' => $this->targetInstitution->id,
                'name' => $this->targetInstitution->name,
                'npsn' => $this->targetInstitution->npsn,
                'level' => $this->targetInstitution->level,
            ] : null),
            'is_external_target' => $this->target_institution_id === null,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'nisn' => $this->student->nisn,
                'nis' => $this->student->nis,
                'name' => $this->student->name,
                'gender' => $this->student->gender,
                'status' => $this->student->status,
            ]),
            'requester' => $this->whenLoaded('requester', fn () => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
                'email' => $this->requester->email,
            ]),
            'approver' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ]),
        ];
    }
}

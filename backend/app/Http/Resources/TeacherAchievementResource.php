<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherAchievementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'employee_id' => $this->employee_id,
            'achievement_type_id' => $this->achievement_type_id,
            'title' => $this->title,
            'achievement_date' => $this->achievement_date?->format('Y-m-d'),
            'point_value' => $this->point_value,
            'level' => $this->level,
            'notes' => $this->notes,
            'evidence_path' => $this->evidence_path,
            'evidence_url' => $this->evidence_path ? asset('storage/' . $this->evidence_path) : null,
            'status' => $this->status,
            'submitted_by' => $this->submitted_by,
            'given_by' => $this->given_by,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'review_notes' => $this->review_notes,
            'academic_year_id' => $this->academic_year_id,
            'semester_id' => $this->semester_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'nip' => $this->employee->nip,
                'nuptk' => $this->employee->nuptk,
                'type' => $this->employee->type,
                'subject' => $this->employee->subject,
            ]),
            'achievement_type' => $this->whenLoaded('achievementType', fn () => [
                'id' => $this->achievementType->id,
                'name' => $this->achievementType->name,
                'code' => $this->achievementType->code,
                'point_value' => $this->achievementType->point_value,
                'category' => $this->achievementType->category,
            ]),
            'submitter' => $this->whenLoaded('submitter', fn () => $this->submitter ? [
                'id' => $this->submitter->id,
                'name' => $this->submitter->name,
            ] : null),
            'giver' => $this->whenLoaded('giver', fn () => $this->giver ? [
                'id' => $this->giver->id,
                'name' => $this->giver->name,
            ] : null),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null),
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
                'code' => $this->academicYear->code,
            ] : null),
            'semester' => $this->whenLoaded('semester', fn () => $this->semester ? [
                'id' => $this->semester->id,
                'name' => $this->semester->name,
            ] : null),
        ];
    }
}

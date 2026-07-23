<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchievementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'student_id' => $this->student_id,
            'achievement_type_id' => $this->achievement_type_id,
            'given_by' => $this->given_by,
            'achievement_date' => $this->achievement_date?->format('Y-m-d'),
            'point_value' => $this->point_value,
            'notes' => $this->notes,
            'status' => $this->status ?? 'dicatat',
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'review_notes' => $this->review_notes,
            'academic_year_id' => $this->academic_year_id,
            'semester_id' => $this->semester_id,
            'created_at' => $this->created_at->toIso8601String(),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'nis' => $this->student->nis,
                'nisn' => $this->student->nisn,
            ]),
            'achievement_type' => $this->whenLoaded('achievementType', fn () => [
                'id' => $this->achievementType->id,
                'name' => $this->achievementType->name,
                'point_value' => $this->achievementType->point_value,
            ]),
            'giver' => $this->whenLoaded('giver', fn () => [
                'id' => $this->giver->id,
                'name' => $this->giver->name,
            ]),
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

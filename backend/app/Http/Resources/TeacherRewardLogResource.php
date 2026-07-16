<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherRewardLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'employee_id' => $this->employee_id,
            'teacher_point_reward_id' => $this->teacher_point_reward_id,
            'reward_name' => $this->reward_name,
            'reward_date' => $this->reward_date?->format('Y-m-d'),
            'score_at_reward' => $this->score_at_reward,
            'notes' => $this->notes,
            'recorded_by' => $this->recorded_by,
            'academic_year_id' => $this->academic_year_id,
            'semester_id' => $this->semester_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'nip' => $this->employee->nip,
            ]),
            'reward' => $this->whenLoaded('reward', fn () => $this->reward ? [
                'id' => $this->reward->id,
                'reward_name' => $this->reward->reward_name,
                'point_min' => $this->reward->point_min,
                'point_max' => $this->reward->point_max,
            ] : null),
            'recorder' => $this->whenLoaded('recorder', fn () => $this->recorder ? [
                'id' => $this->recorder->id,
                'name' => $this->recorder->name,
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

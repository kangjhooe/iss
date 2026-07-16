<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherRewardLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employee,id',
            'teacher_point_reward_id' => 'nullable|exists:teacher_point_rewards,id',
            'reward_name' => 'required_without:teacher_point_reward_id|nullable|string|max:255',
            'reward_date' => 'required|date',
            'score_at_reward' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester_id' => 'nullable|exists:semesters,id',
        ];
    }
}

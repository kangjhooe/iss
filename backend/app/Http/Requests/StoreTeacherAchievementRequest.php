<?php

namespace App\Http\Requests;

use App\Models\TeacherAchievement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employee,id',
            'achievement_type_id' => 'required|exists:teacher_achievement_types,id',
            'title' => 'nullable|string|max:255',
            'achievement_date' => 'required|date',
            'point_value' => 'nullable|integer|min:0|max:1000',
            'level' => ['nullable', 'string', Rule::in(TeacherAchievement::LEVELS)],
            'notes' => 'nullable|string',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester_id' => 'nullable|exists:semesters,id',
        ];
    }
}

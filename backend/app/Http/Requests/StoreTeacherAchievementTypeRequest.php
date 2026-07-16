<?php

namespace App\Http\Requests;

use App\Models\TeacherAchievementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherAchievementTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'point_value' => 'required|integer|min:0|max:500',
            'category' => ['nullable', 'string', Rule::in(TeacherAchievementType::CATEGORIES)],
            'level_multipliers' => 'nullable|array',
            'level_multipliers.sekolah' => 'nullable|numeric|min:0|max:10',
            'level_multipliers.kabupaten' => 'nullable|numeric|min:0|max:10',
            'level_multipliers.provinsi' => 'nullable|numeric|min:0|max:10',
            'level_multipliers.nasional' => 'nullable|numeric|min:0|max:10',
            'level_multipliers.internasional' => 'nullable|numeric|min:0|max:10',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}

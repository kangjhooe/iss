<?php

namespace App\Http\Requests;

use App\Models\AchievementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAchievementTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50',
            'point_value' => 'sometimes|integer|min:0|max:100',
            'level_point_values' => 'nullable|array',
            'level_point_values.*' => 'nullable|integer|min:0|max:100',
            'category' => 'nullable|string|max:50',
            'purpose' => ['nullable', 'string', Rule::in(AchievementType::PURPOSES)],
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

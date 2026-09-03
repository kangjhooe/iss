<?php

namespace App\Http\Requests;

use App\Models\AchievementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAchievementTypeRequest extends FormRequest
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
            'point_value' => 'required|integer|min:0|max:100',
            'level_point_values' => 'nullable|array',
            'level_point_values.*' => 'nullable|integer|min:0|max:100',
            'category' => 'nullable|string|max:50',
            'purpose' => ['nullable', 'string', Rule::in(AchievementType::PURPOSES)],
            'description' => 'nullable|string',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

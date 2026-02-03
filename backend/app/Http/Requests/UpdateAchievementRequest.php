<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'achievement_type_id' => 'required|exists:achievement_types,id',
            'achievement_date' => 'required|date',
            'point_value' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ];
    }
}

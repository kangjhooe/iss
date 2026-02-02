<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:student,id',
            'achievement_type_id' => 'required|exists:achievement_types,id',
            'achievement_date' => 'required|date',
            'point_value' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ];
    }
}

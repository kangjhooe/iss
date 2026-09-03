<?php

namespace App\Http\Requests;

use App\Models\Achievement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'purpose' => ['nullable', 'string', Rule::in(Achievement::PURPOSES)],
            'title' => 'nullable|string|max:255',
            'level' => ['nullable', 'string', Rule::in(Achievement::LEVELS)],
            'rank' => ['nullable', 'string', Rule::in(Achievement::RANKS)],
            'achievement_date' => 'required|date',
            'point_value' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ];
    }
}

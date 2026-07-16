<?php

namespace App\Http\Requests;

use App\Models\TeacherViolationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherViolationTypeRequest extends FormRequest
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
            'point_weight' => 'required|integer|min:0|max:500',
            'category' => ['nullable', 'string', Rule::in(TeacherViolationType::CATEGORIES)],
            'default_sanction' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}

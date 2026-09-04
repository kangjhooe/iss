<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeStructuralPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['sometimes', 'required', 'integer', 'exists:employee,id'],
            'structural_position_id' => ['sometimes', 'required', 'integer', 'exists:structural_positions,id'],
            'employee_decree_id' => ['nullable', 'integer', 'exists:employee_decrees,id'],
            'started_at' => ['sometimes', 'required', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'decree_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentActionLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:student,id',
            'point_threshold_id' => 'nullable|exists:point_thresholds,id',
            'action_name' => 'required|string|max:255',
            'action_date' => 'required|date',
            'notes' => 'nullable|string',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLabScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => 'sometimes|integer|exists:room,id',
            'semester_id' => 'sometimes|integer|exists:semesters,id',
            'class_id' => 'sometimes|integer|exists:class,id',
            'subject_id' => 'sometimes|integer|exists:subjects,id',
            'employee_id' => 'sometimes|integer|exists:employee,id',
            'day_of_week' => 'sometimes|integer|min:1|max:7',
            'period' => 'sometimes|integer|min:1|max:12',
            'duration' => 'sometimes|integer|min:1|max:10',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}

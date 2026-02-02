<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester_id' => 'sometimes|exists:semesters,id',
            'class_id' => 'sometimes|exists:class,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'employee_id' => 'sometimes|exists:employee,id',
            'room_id' => 'nullable|exists:room,id',
            'day_of_week' => 'sometimes|integer|min:1|max:5',
            'period' => 'sometimes|integer|min:1|max:20',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'notes' => 'nullable|string',
        ];
    }
}

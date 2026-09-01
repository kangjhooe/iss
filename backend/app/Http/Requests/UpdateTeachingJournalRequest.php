<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeachingJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester_id' => 'sometimes|required|exists:semesters,id',
            'lesson_schedule_id' => 'nullable|exists:lesson_schedules,id',
            'class_id' => 'sometimes|required|exists:class,id',
            'subject_id' => 'sometimes|required|exists:subjects,id',
            'employee_id' => 'sometimes|required|exists:employee,id',
            'journal_date' => 'sometimes|required|date',
            'period' => 'nullable|integer|min:1|max:20',
            'penilaian_index' => 'nullable|integer|min:1|max:99',
            'material_taught' => 'nullable|string|max:65535',
            'attendance_notes' => 'nullable|string|max:65535',
            'notes' => 'nullable|string|max:65535',
        ];
    }
}

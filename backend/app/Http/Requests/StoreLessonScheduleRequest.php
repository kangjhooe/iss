<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester_id' => 'nullable|exists:semesters,id',
            'class_id' => 'required|exists:class,id',
            'subject_id' => 'required|exists:subjects,id',
            'employee_id' => 'required|exists:employee,id',
            'room_id' => 'nullable|exists:room,id',
            'day_of_week' => 'required|integer|min:1|max:7',
            'period' => 'required|integer|min:1|max:20',
            'duration' => 'nullable|integer|min:1|max:10',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'notes' => 'nullable|string',
            'allow_teacher_conflict' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'class_id.required' => 'Kelas wajib dipilih.',
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'employee_id.required' => 'Guru wajib dipilih.',
            'day_of_week.required' => 'Hari wajib dipilih.',
            'day_of_week.min' => 'Hari harus Senin–Minggu (1–7).',
            'day_of_week.max' => 'Hari harus Senin–Minggu (1–7).',
            'period.required' => 'Jam ke wajib diisi.',
        ];
    }
}

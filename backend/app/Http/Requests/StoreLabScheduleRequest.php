<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => 'required|integer|exists:room,id',
            'semester_id' => 'required|integer|exists:semesters,id',
            'class_id' => 'required|integer|exists:class,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'employee_id' => 'required|integer|exists:employee,id',
            'day_of_week' => 'required|integer|min:1|max:7',
            'period' => 'required|integer|min:1|max:12',
            'duration' => 'nullable|integer|min:1|max:10',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Ruang lab wajib dipilih.',
            'semester_id.required' => 'Semester wajib dipilih.',
            'class_id.required' => 'Kelas wajib dipilih.',
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'employee_id.required' => 'Guru wajib dipilih.',
            'day_of_week.required' => 'Hari wajib dipilih.',
            'period.required' => 'Jam ke wajib diisi.',
        ];
    }
}

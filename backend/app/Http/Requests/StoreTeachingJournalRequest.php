<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeachingJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester_id' => 'required|exists:semesters,id',
            'lesson_schedule_id' => 'nullable|exists:lesson_schedules,id',
            'class_id' => 'required|exists:class,id',
            'subject_id' => 'required|exists:subjects,id',
            'employee_id' => 'nullable|exists:employee,id',
            'journal_date' => 'required|date',
            'period' => 'nullable|integer|min:1|max:20',
            'penilaian_index' => 'nullable|integer|min:1|max:99',
            'material_taught' => 'nullable|string|max:65535',
            'attendance_notes' => 'nullable|string|max:65535',
            'notes' => 'nullable|string|max:65535',
        ];
    }

    public function messages(): array
    {
        return [
            'semester_id.required' => 'Semester wajib dipilih.',
            'semester_id.exists' => 'Semester tidak ditemukan.',
            'class_id.required' => 'Kelas wajib dipilih.',
            'class_id.exists' => 'Kelas tidak ditemukan.',
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'subject_id.exists' => 'Mata pelajaran tidak ditemukan.',
            'employee_id.required' => 'Guru wajib dipilih.',
            'employee_id.exists' => 'Guru tidak ditemukan.',
            'journal_date.required' => 'Tanggal jurnal wajib diisi.',
        ];
    }
}

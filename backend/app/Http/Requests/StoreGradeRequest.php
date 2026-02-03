<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester_id' => 'required|exists:semesters,id',
            'class_id' => 'required|exists:class,id',
            'subject_id' => 'required|exists:subjects,id',
            'student_id' => 'required|exists:student,id',
            'employee_id' => 'nullable|exists:employee,id',
            'grade_type' => 'required|in:uh,uts,uas,tugas,nilai_akhir',
            'value' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'semester_id.required' => 'Semester wajib dipilih.',
            'class_id.required' => 'Kelas wajib dipilih.',
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'student_id.required' => 'Siswa wajib dipilih.',
            'grade_type.required' => 'Jenis nilai wajib dipilih.',
            'grade_type.in' => 'Jenis nilai tidak valid.',
            'value.required' => 'Nilai wajib diisi.',
            'value.min' => 'Nilai minimal 0.',
            'value.max' => 'Nilai maksimal 100.',
        ];
    }
}

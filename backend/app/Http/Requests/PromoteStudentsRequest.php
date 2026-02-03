<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromoteStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source_class_id' => 'required|integer|exists:class,id',
            'source_academic_year_id' => 'required|integer|exists:academic_years,id',
            'target_class_id' => 'required|integer|exists:class,id',
            'target_academic_year_id' => 'required|integer|exists:academic_years,id',
            'target_semester_id' => 'nullable|integer|exists:semesters,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'integer|exists:student,id',
        ];
    }

    public function messages(): array
    {
        return [
            'source_class_id.required' => 'Kelas sumber wajib dipilih.',
            'source_class_id.exists' => 'Kelas sumber tidak ditemukan.',
            'source_academic_year_id.required' => 'Tahun ajaran sumber wajib dipilih.',
            'source_academic_year_id.exists' => 'Tahun ajaran sumber tidak ditemukan.',
            'target_class_id.required' => 'Kelas tujuan wajib dipilih.',
            'target_class_id.exists' => 'Kelas tujuan tidak ditemukan.',
            'target_academic_year_id.required' => 'Tahun ajaran tujuan wajib dipilih.',
            'target_academic_year_id.exists' => 'Tahun ajaran tujuan tidak ditemukan.',
            'target_semester_id.exists' => 'Semester tujuan tidak ditemukan.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertSubjectKkmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester_id' => 'required|integer|exists:semesters,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'grade' => 'required|integer|min:1|max:12',
            'kkm' => 'required|numeric|min:0|max:100',
            // Opsional: jika dikirim, grade diambil dari kelas ini (validasi konsistensi)
            'class_id' => 'nullable|integer|exists:class,id',
        ];
    }

    public function messages(): array
    {
        return [
            'kkm.required' => 'KKM wajib diisi.',
            'kkm.min' => 'KKM minimal 0.',
            'kkm.max' => 'KKM maksimal 100.',
            'grade.required' => 'Tingkat wajib diisi.',
        ];
    }
}

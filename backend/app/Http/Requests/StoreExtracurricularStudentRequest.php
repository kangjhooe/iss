<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExtracurricularStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:student,id',
        ];
    }

    public function messages(): array
    {
        return [
            'student_ids.required' => 'Pilih minimal satu siswa.',
            'student_ids.min' => 'Pilih minimal satu siswa.',
            'student_ids.*.exists' => 'Siswa tidak ditemukan.',
        ];
    }
}

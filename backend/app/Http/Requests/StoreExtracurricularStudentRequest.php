<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExtracurricularStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $ids = $this->input('student_ids');
        if (!is_array($ids)) {
            return;
        }

        $this->merge([
            'student_ids' => array_values(array_unique(array_filter(
                array_map('intval', $ids),
                fn ($id) => $id > 0
            ))),
        ]);
    }

    public function rules(): array
    {
        return [
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => [
                'required',
                'integer',
                Rule::exists('student', 'id')->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
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

<?php

namespace App\Http\Requests;

use App\Models\StudentChangeRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|integer|exists:student,id',
            'field_name' => ['required', 'string', 'in:' . implode(',', StudentChangeRequest::ALLOWED_FIELDS)],
            'new_value' => 'nullable|string|max:65535',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'ID siswa wajib diisi',
            'student_id.exists' => 'Siswa tidak ditemukan',
            'field_name.required' => 'Field wajib dipilih',
            'field_name.in' => 'Field tidak diizinkan untuk diubah oleh siswa',
        ];
    }
}

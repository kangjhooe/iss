<?php

namespace App\Http\Requests;

use App\Models\TeacherChangeRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        $user->load(['teacherProfile', 'employeeProfile']);
        return ($user->teacherProfile || $user->employeeProfile) !== null;
    }

    public function rules(): array
    {
        $rules = [
            'field_name' => ['required', 'string', 'in:' . implode(',', TeacherChangeRequest::ALLOWED_FIELDS)],
            'new_value' => ['nullable', 'string', 'max:65535'],
        ];

        $fieldName = $this->input('field_name');
        if ($fieldName === 'email') {
            $rules['new_value'] = ['nullable', 'string', 'email', 'max:255'];
        }
        if (in_array($fieldName, ['birth_date', 'certification_date'], true)) {
            $rules['new_value'] = ['nullable', 'date'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'field_name.required' => 'Field wajib dipilih',
            'field_name.in' => 'Field tidak diizinkan untuk diubah',
            'new_value.email' => 'Format email tidak valid',
            'new_value.date' => 'Format tanggal tidak valid (YYYY-MM-DD)',
        ];
    }
}

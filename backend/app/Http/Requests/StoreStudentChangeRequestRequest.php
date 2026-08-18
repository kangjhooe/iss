<?php

namespace App\Http\Requests;

use App\Models\StudentChangeRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    public function rules(): array
    {
        $studentId = $this->user()?->studentProfile?->id;

        $rules = [
            'student_id' => 'required|integer|exists:student,id',
            'field_name' => ['required', 'string', 'in:' . implode(',', StudentChangeRequest::APPROVAL_FIELDS)],
            'new_value' => ['nullable', 'string', 'max:65535'],
        ];

        $fieldName = $this->input('field_name');

        switch ($fieldName) {
            case 'email':
                $rules['new_value'] = ['nullable', 'string', 'email', 'max:255'];
                break;
            case 'name':
            case 'father_name':
            case 'mother_name':
            case 'guardian_name':
            case 'birth_place':
                $rules['new_value'] = ['required', 'string', 'max:255'];
                break;
            case 'nik':
            case 'father_nik':
            case 'mother_nik':
            case 'guardian_nik':
                $rules['new_value'] = ['nullable', 'string', 'size:16', 'regex:/^[0-9]{16}$/'];
                if ($fieldName === 'nik') {
                    $rules['new_value'][] = Rule::unique('student', 'nik')->ignore($studentId);
                }
                break;
            case 'nis':
                $rules['new_value'] = ['nullable', 'string', 'max:50'];
                break;
            case 'nisn':
                $rules['new_value'] = [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('student', 'nisn')->ignore($studentId),
                ];
                break;
            case 'no_kk':
                $rules['new_value'] = ['nullable', 'string', 'max:16'];
                break;
            case 'gender':
                $rules['new_value'] = ['required', 'in:L,P'];
                break;
            case 'birth_date':
                $rules['new_value'] = ['nullable', 'date'];
                break;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'ID siswa wajib diisi',
            'student_id.exists' => 'Siswa tidak ditemukan',
            'field_name.required' => 'Field wajib dipilih',
            'field_name.in' => 'Field tidak diizinkan untuk diajukan perubahan',
            'new_value.required' => 'Nilai baru wajib diisi',
            'new_value.email' => 'Format email tidak valid',
            'new_value.date' => 'Format tanggal tidak valid (YYYY-MM-DD)',
            'new_value.size' => 'NIK harus 16 digit',
            'new_value.regex' => 'NIK harus berupa angka 16 digit',
            'new_value.unique' => 'Nilai sudah terdaftar pada siswa lain',
            'new_value.in' => 'Nilai tidak valid untuk field ini',
        ];
    }
}

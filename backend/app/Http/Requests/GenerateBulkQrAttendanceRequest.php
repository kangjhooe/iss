<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GenerateBulkQrAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_id' => ['nullable', 'integer'],
            'student_ids' => ['nullable', 'array', 'max:300'],
            'student_ids.*' => ['integer'],
            'employee_ids' => ['nullable', 'array', 'max:300'],
            'employee_ids.*' => ['integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['student_ids', 'employee_ids'] as $key) {
            $value = $this->input($key);
            if (is_string($value) && $value !== '') {
                $this->merge([
                    $key => array_values(array_filter(array_map('intval', explode(',', $value)))),
                ]);
            }
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->routeIs('qr-attendance.employee.generate-bulk', 'qr-attendance.employee.print-pdf')
                ? 'employee'
                : 'student';

            if ($type === 'student' && !$this->filled('class_id') && !$this->filled('student_ids')) {
                $validator->errors()->add('class_id', 'Pilih kelas atau daftar siswa untuk generate QR.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'student_ids.max' => 'Maksimal 300 siswa per generate.',
            'employee_ids.max' => 'Maksimal 300 pegawai per generate.',
        ];
    }
}

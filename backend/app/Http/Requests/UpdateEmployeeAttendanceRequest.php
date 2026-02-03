<?php

namespace App\Http\Requests;

use App\Models\EmployeeAttendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                'max:32',
                Rule::in(array_keys(EmployeeAttendance::STATUSES)),
            ],
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => [
                'nullable',
                'date_format:H:i',
                function (string $attribute, ?string $value, \Closure $fail): void {
                    if (!$value) {
                        return;
                    }
                    $checkIn = $this->input('check_in_time');
                    if ($checkIn && $value < $checkIn) {
                        $fail('Jam keluar harus setelah atau sama dengan jam masuk.');
                    }
                },
            ],
            'notes' => 'nullable|string|max:65535',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status kehadiran wajib dipilih.',
            'status.in' => 'Status kehadiran tidak valid.',
        ];
    }
}

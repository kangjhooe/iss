<?php

namespace App\Http\Requests;

use App\Models\EmployeeAttendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkEmployeeAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'attendances' => 'required|array|min:1',
            'attendances.*.employee_id' => 'required|exists:employee,id',
            'attendances.*.status' => [
                'required',
                'string',
                'max:32',
                Rule::in(array_keys(EmployeeAttendance::STATUSES)),
            ],
            'attendances.*.check_in_time' => 'nullable|date_format:H:i',
            'attendances.*.check_out_time' => [
                'nullable',
                'date_format:H:i',
                function (string $attribute, ?string $value, \Closure $fail): void {
                    if (!$value) {
                        return;
                    }
                    $index = (int) preg_replace('/^attendances\.(\d+)\.check_out_time$/', '$1', $attribute);
                    $checkIn = $this->input("attendances.{$index}.check_in_time");
                    if ($checkIn && $value < $checkIn) {
                        $fail('Jam keluar harus setelah atau sama dengan jam masuk.');
                    }
                },
            ],
            'attendances.*.notes' => 'nullable|string|max:65535',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal wajib diisi.',
            'attendances.required' => 'Data absensi pegawai wajib diisi.',
            'attendances.*.employee_id.required' => 'Pegawai wajib dipilih.',
            'attendances.*.employee_id.exists' => 'Pegawai tidak ditemukan.',
            'attendances.*.status.required' => 'Status kehadiran wajib dipilih.',
            'attendances.*.status.in' => 'Status kehadiran tidak valid.',
        ];
    }
}

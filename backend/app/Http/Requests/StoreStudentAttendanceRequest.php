<?php

namespace App\Http\Requests;

use App\Models\StudentAttendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teaching_journal_id' => 'required|exists:teaching_journals,id',
            'attendances' => 'required|array|min:1',
            'attendances.*.student_id' => 'required|exists:student,id',
            'attendances.*.status' => [
                'required',
                'string',
                'max:32',
                Rule::in(array_keys(StudentAttendance::STATUSES)),
            ],
            'attendances.*.notes' => 'nullable|string|max:65535',
        ];
    }

    public function messages(): array
    {
        return [
            'teaching_journal_id.required' => 'Jurnal mengajar wajib dipilih.',
            'teaching_journal_id.exists' => 'Jurnal mengajar tidak ditemukan.',
            'attendances.required' => 'Data absensi siswa wajib diisi.',
            'attendances.*.student_id.required' => 'Siswa wajib dipilih.',
            'attendances.*.student_id.exists' => 'Siswa tidak ditemukan.',
            'attendances.*.status.required' => 'Status kehadiran wajib dipilih.',
            'attendances.*.status.in' => 'Status kehadiran tidak valid.',
        ];
    }
}

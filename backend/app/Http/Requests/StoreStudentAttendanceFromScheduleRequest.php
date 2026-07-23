<?php

namespace App\Http\Requests;

use App\Models\StudentAttendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentAttendanceFromScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $ids = $this->input('lesson_schedule_ids');
        if (is_string($ids) && $ids !== '') {
            $parsed = array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', $ids))));
            $this->merge(['lesson_schedule_ids' => $parsed]);
        }

        if (!$this->filled('lesson_schedule_ids') && $this->filled('lesson_schedule_id')) {
            $this->merge([
                'lesson_schedule_ids' => [(int) $this->input('lesson_schedule_id')],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'lesson_schedule_id' => 'nullable|integer|exists:lesson_schedules,id',
            'lesson_schedule_ids' => 'required|array|min:1',
            'lesson_schedule_ids.*' => 'integer|exists:lesson_schedules,id',
            'date' => 'required|date',
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
            'lesson_schedule_ids.required' => 'Slot jadwal wajib dipilih.',
            'lesson_schedule_ids.array' => 'Slot jadwal tidak valid.',
            'lesson_schedule_ids.min' => 'Slot jadwal wajib dipilih.',
            'lesson_schedule_ids.*.exists' => 'Slot jadwal tidak ditemukan.',
            'date.required' => 'Tanggal absensi wajib diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'attendances.required' => 'Data absensi siswa wajib diisi.',
            'attendances.*.student_id.required' => 'Siswa wajib dipilih.',
            'attendances.*.student_id.exists' => 'Siswa tidak ditemukan.',
            'attendances.*.status.required' => 'Status kehadiran wajib dipilih.',
            'attendances.*.status.in' => 'Status kehadiran tidak valid.',
        ];
    }
}

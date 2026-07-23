<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrepareStudentAttendanceFromScheduleRequest extends FormRequest
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
        ];
    }
}

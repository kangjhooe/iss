<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePiketIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'incident_date' => 'required|date',
            'incident_type' => 'required|in:kelas_kosong,terlambat_guru,terlambat_siswa,lainnya',
            'period' => 'nullable|integer|between:1,20',
            'class_id' => 'nullable|integer|exists:class,id',
            'subject_id' => 'nullable|integer|exists:subjects,id',
            'employee_id' => 'nullable|integer|exists:employee,id',
            'student_id' => 'nullable|integer|exists:student,id',
            'lesson_schedule_id' => 'nullable|integer|exists:lesson_schedules,id',
            'piket_log_id' => 'nullable|integer|exists:piket_logs,id',
            'detected_at' => 'nullable|date_format:H:i',
            'minutes_late' => 'nullable|integer|min:0|max:600',
            'description' => 'nullable|string|max:5000',
            'status' => 'nullable|in:open,confirmed,resolved,dismissed',
        ];
    }
}

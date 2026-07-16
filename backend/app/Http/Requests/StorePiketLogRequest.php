<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePiketLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'duty_date' => 'required|date',
            'employee_id' => 'nullable|integer|exists:employee,id',
            'piket_schedule_id' => 'nullable|integer|exists:piket_schedules,id',
            'summary' => 'nullable|string|max:5000',
            'handoff_notes' => 'nullable|string|max:5000',
            'status' => 'nullable|in:draft,submitted',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExtracurricularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'supervisor_employee_id' => 'nullable|exists:employee,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester_id' => 'nullable|exists:semesters,id',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'sometimes|string|in:Aktif,Nonaktif',
            'day_of_week' => 'nullable|integer|min:1|max:5',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'room_id' => 'nullable|exists:room,id',
        ];
    }
}

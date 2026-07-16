<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherViolationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employee,id',
            'violation_type_id' => 'required|exists:teacher_violation_types,id',
            'violation_date' => 'required|date',
            'point_value' => 'nullable|integer|min:0|max:1000',
            'notes' => 'nullable|string',
            'sanction' => 'nullable|string|max:255',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
            'force_pending' => 'nullable|boolean',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester_id' => 'nullable|exists:semesters,id',
        ];
    }
}

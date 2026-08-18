<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUksVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'sometimes|required|exists:student,id',
            'recorded_by' => 'nullable|exists:user,id',
            'uks_visit_type_id' => 'nullable|exists:uks_visit_types,id',
            'visit_date' => 'sometimes|required|date',
            'status' => 'nullable|in:selesai,observasi,rujuk',
            'complaint' => 'nullable|string|max:5000',
            'action_taken' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:5000',
            'height_cm' => 'nullable|numeric|min:0|max:300',
            'weight_kg' => 'nullable|numeric|min:0|max:500',
            'blood_pressure' => 'nullable|string|max:20',
            'temperature_c' => 'nullable|numeric|min:30|max:45',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExtracurricularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution_id' => 'sometimes|exists:institution,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'supervisor_employee_id' => 'nullable|exists:employee,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester_id' => 'nullable|exists:semesters,id',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'sometimes|string|in:Aktif,Nonaktif',
            'day_of_week' => 'nullable|integer|min:1|max:5',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'room_id' => 'nullable|exists:room,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama ekstrakurikuler wajib diisi.',
            'supervisor_employee_id.exists' => 'Guru penanggung jawab tidak ditemukan.',
        ];
    }
}

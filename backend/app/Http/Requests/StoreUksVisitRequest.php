<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUksVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:student,id',
            'recorded_by' => 'nullable|exists:user,id',
            'uks_visit_type_id' => 'nullable|exists:uks_visit_types,id',
            'visit_date' => 'required|date',
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

    public function messages(): array
    {
        return [
            'student_id.required' => 'Siswa wajib dipilih.',
            'student_id.exists' => 'Siswa tidak ditemukan.',
            'visit_date.required' => 'Tanggal kunjungan wajib diisi.',
        ];
    }
}

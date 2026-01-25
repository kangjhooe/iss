<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'institution_id' => 'sometimes|exists:institution,id',
            'room_id' => 'nullable|exists:room,id',
            'teacher_id' => 'nullable|exists:teacher,id',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'grade' => 'nullable|integer|min:1|max:12',
            // academic_year_id tidak perlu di-require, akan di-set otomatis dari active_academic_year_id
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester_id' => 'nullable|exists:semesters,id',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'nullable|in:Aktif,Nonaktif',
            'description' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama kelas wajib diisi',
            'academic_year_id.exists' => 'Tahun ajaran tidak ditemukan',
            'grade.integer' => 'Tingkat kelas harus berupa angka',
            'grade.min' => 'Tingkat kelas minimal 1',
            'grade.max' => 'Tingkat kelas maksimal 12',
            'capacity.integer' => 'Kapasitas harus berupa angka',
            'capacity.min' => 'Kapasitas minimal 1',
            'room_id.exists' => 'Ruangan tidak ditemukan',
            'teacher_id.exists' => 'Guru tidak ditemukan',
            'institution_id.exists' => 'Instansi tidak ditemukan',
        ];
    }
}

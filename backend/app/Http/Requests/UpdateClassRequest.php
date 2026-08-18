<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRequest extends FormRequest
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
            'room_id' => 'nullable|exists:room,id',
            'teacher_id' => ['nullable', Rule::exists('employee', 'id')->where('type', 'Guru')],
            'code' => 'nullable|string|max:50',
            'name' => 'sometimes|required|string|max:255',
            'grade' => 'nullable|integer|min:1|max:12',
            'program_keahlian_id' => 'nullable|integer|exists:program_keahlian,id',
            // academic_year_id tidak bisa diubah setelah kelas dibuat
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
        ];
    }
}

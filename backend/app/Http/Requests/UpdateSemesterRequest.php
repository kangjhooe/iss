<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSemesterRequest extends FormRequest
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
            'academic_year_id' => 'sometimes|required|exists:academic_years,id',
            'name' => 'sometimes|required|in:Ganjil,Genap',
            'order' => 'nullable|integer|min:1|max:2',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'status' => 'nullable|in:Aktif,Selesai,Draft',
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
            'academic_year_id.required' => 'Tahun ajaran wajib diisi',
            'academic_year_id.exists' => 'Tahun ajaran tidak ditemukan',
            'name.required' => 'Nama semester wajib diisi',
            'name.in' => 'Nama semester harus Ganjil atau Genap',
            'start_date.required' => 'Tanggal mulai wajib diisi',
            'start_date.date' => 'Tanggal mulai harus berupa tanggal yang valid',
            'end_date.required' => 'Tanggal akhir wajib diisi',
            'end_date.date' => 'Tanggal akhir harus berupa tanggal yang valid',
            'end_date.after' => 'Tanggal akhir harus setelah tanggal mulai',
            'status.in' => 'Status harus salah satu dari: Aktif, Selesai, Draft',
        ];
    }
}

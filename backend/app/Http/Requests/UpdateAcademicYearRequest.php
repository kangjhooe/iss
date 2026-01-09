<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicYearRequest extends FormRequest
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
            'code' => 'sometimes|required|string|max:20|regex:/^\d{4}\/\d{4}$/',
            'name' => 'nullable|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'status' => 'nullable|in:Aktif,Arsip,Draft',
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
            'code.required' => 'Kode tahun ajaran wajib diisi',
            'code.regex' => 'Format tahun ajaran tidak valid. Gunakan format: YYYY/YYYY (contoh: 2025/2026)',
            'start_date.required' => 'Tanggal mulai wajib diisi',
            'start_date.date' => 'Tanggal mulai harus berupa tanggal yang valid',
            'end_date.required' => 'Tanggal akhir wajib diisi',
            'end_date.date' => 'Tanggal akhir harus berupa tanggal yang valid',
            'end_date.after' => 'Tanggal akhir harus setelah tanggal mulai',
            'status.in' => 'Status harus salah satu dari: Aktif, Arsip, Draft',
        ];
    }
}

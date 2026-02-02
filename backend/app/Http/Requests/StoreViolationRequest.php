<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreViolationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:student,id',
            'violation_type_id' => 'required|exists:violation_types,id',
            'violation_date' => 'required|date',
            'sanction' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Siswa wajib dipilih.',
            'student_id.exists' => 'Siswa tidak ditemukan.',
            'violation_type_id.required' => 'Jenis pelanggaran wajib dipilih.',
            'violation_type_id.exists' => 'Jenis pelanggaran tidak ditemukan.',
            'violation_date.required' => 'Tanggal pelanggaran wajib diisi.',
        ];
    }
}

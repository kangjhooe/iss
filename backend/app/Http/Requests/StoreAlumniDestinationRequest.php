<?php

namespace App\Http\Requests;

use App\Models\AlumniDestination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlumniDestinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|integer|exists:student,id',
            'destination_type' => [
                'required',
                'string',
                Rule::in(array_keys(AlumniDestination::DESTINATION_TYPES)),
            ],
            'destination_name' => 'required|string|max:255',
            'program_or_position' => 'nullable|string|max:255',
            'year_entered' => 'nullable|integer|min:1990|max:' . (date('Y') + 2),
            'notes' => 'nullable|string|max:65535',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Siswa (alumni) wajib dipilih.',
            'student_id.exists' => 'Siswa tidak ditemukan.',
            'destination_type.required' => 'Jenis destinasi wajib dipilih.',
            'destination_type.in' => 'Jenis destinasi tidak valid.',
            'destination_name.required' => 'Nama sekolah/PT/tempat kerja wajib diisi.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCounselingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:student,id',
            'counselor_id' => 'required|exists:user,id',
            'counseling_type_id' => 'nullable|exists:counseling_types,id',
            'session_date' => 'required|date',
            'status' => 'nullable|in:jadwal,berlangsung,selesai,dibatalkan',
            'summary' => 'nullable|string',
            'follow_up_notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Siswa wajib dipilih.',
            'student_id.exists' => 'Siswa tidak ditemukan.',
            'counselor_id.required' => 'Konselor wajib dipilih.',
            'counselor_id.exists' => 'Konselor tidak ditemukan.',
            'session_date.required' => 'Tanggal sesi wajib diisi.',
        ];
    }
}

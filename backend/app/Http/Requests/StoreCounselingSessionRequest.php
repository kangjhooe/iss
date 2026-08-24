<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCounselingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institutionId = $this->user()?->currentInstitutionId();

        return [
            'student_id' => 'required|exists:student,id',
            'counselor_id' => [
                'required',
                Rule::exists('user', 'id')->where(function ($q) use ($institutionId) {
                    $q->whereIn('role', User::COUNSELOR_ROLES);
                    if ($institutionId) {
                        $q->where('institution_id', $institutionId);
                    }
                }),
            ],
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
            'counselor_id.exists' => 'Konselor harus guru atau staf sekolah.',
            'session_date.required' => 'Tanggal sesi wajib diisi.',
        ];
    }
}

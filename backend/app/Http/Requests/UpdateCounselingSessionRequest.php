<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCounselingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institutionId = $this->user()?->currentInstitutionId();

        return [
            'counselor_id' => [
                'sometimes',
                Rule::exists('user', 'id')->where(function ($q) use ($institutionId) {
                    $q->whereIn('role', User::COUNSELOR_ROLES);
                    if ($institutionId) {
                        $q->where('institution_id', $institutionId);
                    }
                }),
            ],
            'counseling_type_id' => 'nullable|exists:counseling_types,id',
            'session_date' => 'sometimes|date',
            'status' => 'sometimes|in:jadwal,berlangsung,selesai,dibatalkan',
            'summary' => 'nullable|string',
            'follow_up_notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'counselor_id.exists' => 'Konselor harus guru atau staf sekolah.',
        ];
    }
}

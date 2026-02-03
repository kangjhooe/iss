<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCounselingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'counselor_id' => 'sometimes|exists:user,id',
            'counseling_type_id' => 'nullable|exists:counseling_types,id',
            'session_date' => 'sometimes|date',
            'status' => 'sometimes|in:jadwal,berlangsung,selesai,dibatalkan',
            'summary' => 'nullable|string',
            'follow_up_notes' => 'nullable|string',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateViolationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'violation_type_id' => 'sometimes|exists:violation_types,id',
            'violation_date' => 'sometimes|date',
            'sanction' => 'nullable|string|max:255',
            'status' => 'sometimes|in:dicatat,sanksi_diberikan,follow_up,selesai',
            // pending/ditolak hanya via approve/reject
            'description' => 'nullable|string',
            'follow_up_notes' => 'nullable|string',
        ];
    }
}

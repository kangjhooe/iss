<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExtracurricularStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|string|in:aktif,keluar,lulus',
            'left_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}

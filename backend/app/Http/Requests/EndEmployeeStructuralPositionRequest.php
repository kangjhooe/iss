<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EndEmployeeStructuralPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ended_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelStudentMutationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mutation = $this->route('student_mutation');

        return $mutation && $mutation->canRequestCancelBy($this->user());
    }

    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.max' => 'Alasan pembatalan maksimal 500 karakter.',
        ];
    }
}

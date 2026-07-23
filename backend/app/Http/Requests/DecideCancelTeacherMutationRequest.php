<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecideCancelTeacherMutationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mutation = $this->route('teacher_mutation');

        return $mutation && $mutation->canDecideCancelBy($this->user());
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Tindakan wajib diisi.',
            'action.in' => 'Tindakan harus berupa approve atau reject.',
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi jika menolak pembatalan.',
        ];
    }
}

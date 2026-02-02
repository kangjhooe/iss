<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveStudentMutationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $mutation = $this->route('student_mutation');
        return $mutation && $mutation->canBeApprovedBy($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Tindakan wajib diisi.',
            'action.in' => 'Tindakan harus berupa approve atau reject.',
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi jika menolak.',
        ];
    }
}

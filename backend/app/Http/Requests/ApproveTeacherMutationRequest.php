<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class ApproveTeacherMutationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $mutation = $this->route('teacher_mutation');
        $user = $this->user();
        if (!$mutation || !$user) {
            return false;
        }

        $action = $this->input('action');
        if ($action === 'reject') {
            return $mutation->canBeRejectedBy($user);
        }

        return $mutation->canBeApprovedBy($user);
    }

    protected function failedAuthorization()
    {
        throw new AuthorizationException('Anda tidak berwenang menyetujui atau menolak permohonan mutasi ini.');
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

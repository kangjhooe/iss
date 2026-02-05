<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveStudentChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->isSuperAdmin() || $user->isInstitutionAdmin() || $user->isAdmin());
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Tindakan wajib diisi',
            'action.in' => 'Tindakan harus berupa approve atau reject',
        ];
    }
}

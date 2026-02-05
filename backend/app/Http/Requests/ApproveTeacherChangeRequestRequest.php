<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveTeacherChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminOrSuperAdmin() || $this->user()?->isInstitutionAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Action wajib diisi',
            'action.in' => 'Action harus approve atau reject',
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi jika menolak',
        ];
    }
}

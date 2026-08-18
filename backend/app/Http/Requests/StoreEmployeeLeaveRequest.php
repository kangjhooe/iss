<?php

namespace App\Http\Requests;

use App\Models\EmployeeLeaveRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Portal guru/staff: /employee-leaves/my — isi employee_id dari profil sendiri.
        if ($this->is('api/v1/employee-leaves/my') || $this->routeIs('employee-leaves.my.store')) {
            $user = $this->user();
            $user?->loadMissing(['teacherProfile', 'employeeProfile']);
            $profile = $user?->teacherProfile ?? $user?->employeeProfile;
            if ($profile) {
                $this->merge([
                    'employee_id' => $profile->id,
                    'status' => 'pending',
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employee,id'],
            'leave_type' => ['required', 'string', Rule::in(array_keys(EmployeeLeaveRequest::TYPES))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'string', Rule::in(['pending', 'approved'])],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:2048'],
        ];
    }
}

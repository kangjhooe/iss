<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\TeacherMutation;
use App\Support\InstitutionContext;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherMutationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->role, ['admin', 'institution_admin'], true) && $user->institution_id;
    }

    /**
     * Institusi aktif (header/cookie), fallback ke sekolah induk user.
     */
    protected function activeInstitutionId(): ?int
    {
        $user = $this->user();
        if (!$user) {
            return null;
        }

        return InstitutionContext::resolveActiveInstitutionId($user, $this)
            ?? ($user->institution_id ? (int) $user->institution_id : null);
    }

    /**
     * Get the validation rules that apply to the request.
     * Tidak ada batasan jenjang untuk mutasi guru.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $external = $this->boolean('external');
        $institutionId = $this->activeInstitutionId();

        return [
            'external' => 'sometimes|boolean',
            'target_npsn' => [
                'required',
                'string',
                'size:8',
                'regex:/^[0-9]{8}$/',
                function ($attribute, $value, $fail) use ($external, $institutionId) {
                    if ($external) {
                        return;
                    }
                    if (!$institutionId) {
                        $fail('Sekolah asal tidak ditemukan.');
                        return;
                    }
                    $target = Institution::where('npsn', $value)->where('is_active', true)->first();
                    if (!$target) {
                        $fail('Sekolah tujuan dengan NPSN tersebut tidak ditemukan atau tidak aktif.');
                        return;
                    }
                    if ((int) $target->id === (int) $institutionId) {
                        $fail('Sekolah tujuan harus berbeda dengan sekolah asal.');
                    }
                },
            ],
            'target_school_name' => 'required_if:external,true|nullable|string|max:255',
            'nik' => [
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]{16}$/',
                function ($attribute, $value, $fail) use ($external, $institutionId) {
                    if (!$institutionId) {
                        $fail('Sekolah asal tidak ditemukan.');
                        return;
                    }
                    try {
                        $employee = app(\App\Services\TeacherMutationService::class)
                            ->findOutgoingTeacherByNik($value, $institutionId);
                    } catch (\InvalidArgumentException $e) {
                        $fail($e->getMessage());
                        return;
                    }
                    $pending = TeacherMutation::where('employee_id', $employee->id)
                        ->pending()
                        ->exists();
                    if ($pending) {
                        $fail('Guru ini masih memiliki permohonan mutasi yang menunggu persetujuan.');
                        return;
                    }
                    if ($external) {
                        return;
                    }
                    $targetNpsn = $this->input('target_npsn');
                    $target = Institution::where('npsn', $targetNpsn)->first();
                    if ($target) {
                        $pendingToTarget = TeacherMutation::where('employee_id', $employee->id)
                            ->where('target_institution_id', $target->id)
                            ->pending()
                            ->exists();
                        if ($pendingToTarget) {
                            $fail('Guru ini sudah memiliki permohonan mutasi pending ke sekolah tujuan tersebut.');
                        }
                    }
                },
            ],
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
            'target_npsn.required' => 'NPSN sekolah tujuan wajib diisi.',
            'target_npsn.size' => 'NPSN harus 8 digit.',
            'target_npsn.regex' => 'NPSN harus berupa 8 digit angka.',
            'target_school_name.required_if' => 'Nama sekolah tujuan wajib diisi untuk mutasi ke sekolah luar sistem.',
            'nik.required' => 'NIK guru wajib diisi.',
            'nik.size' => 'NIK harus 16 digit.',
            'nik.regex' => 'NIK harus berupa 16 digit angka.',
        ];
    }
}

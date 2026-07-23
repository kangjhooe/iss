<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\TeacherMutation;
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
     * Get the validation rules that apply to the request.
     * Tidak ada batasan jenjang untuk mutasi guru.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $external = $this->boolean('external');

        return [
            'external' => 'sometimes|boolean',
            'target_npsn' => [
                'required',
                'string',
                'size:8',
                'regex:/^[0-9]{8}$/',
                function ($attribute, $value, $fail) use ($external) {
                    if ($external) {
                        return;
                    }
                    $originInstitution = Institution::find($this->user()->institution_id);
                    if (!$originInstitution) {
                        $fail('Sekolah asal tidak ditemukan.');
                        return;
                    }
                    $target = Institution::where('npsn', $value)->where('is_active', true)->first();
                    if (!$target) {
                        $fail('Sekolah tujuan dengan NPSN tersebut tidak ditemukan atau tidak aktif.');
                        return;
                    }
                    if ((int) $target->id === (int) $originInstitution->id) {
                        $fail('Sekolah tujuan harus berbeda dengan sekolah asal.');
                    }
                },
            ],
            'target_school_name' => 'required_if:external,true|nullable|string|max:255',
            'nuptk' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($external) {
                    $institutionId = $this->user()->institution_id;
                    $employee = Employee::where('nuptk', $value)
                        ->where('institution_id', $institutionId)
                        ->where('type', 'Guru')
                        ->where('status', 'Aktif')
                        ->first();
                    if (!$employee) {
                        $fail('Guru dengan NUPTK tersebut tidak ditemukan di sekolah Anda atau status tidak aktif.');
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
            'nuptk.required' => 'NUPTK guru wajib diisi.',
        ];
    }
}

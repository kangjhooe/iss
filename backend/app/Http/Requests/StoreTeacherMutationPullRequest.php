<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\TeacherMutation;
use App\Support\InstitutionContext;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherMutationPullRequest extends FormRequest
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
     * User = admin sekolah TARGET (penarik). Body: origin_npsn (sekolah asal), nik (guru di sekolah asal).
     * Tidak ada batasan jenjang untuk mutasi guru.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $external = $this->boolean('external');
        $targetInstitutionId = $this->activeInstitutionId();

        return [
            'external' => 'sometimes|boolean',
            'origin_npsn' => [
                'required',
                'string',
                'size:8',
                'regex:/^[0-9]{8}$/',
                function ($attribute, $value, $fail) use ($external, $targetInstitutionId) {
                    if ($external) {
                        return;
                    }
                    if (!$targetInstitutionId) {
                        $fail('Sekolah Anda tidak ditemukan.');
                        return;
                    }
                    $origin = Institution::where('npsn', $value)->where('is_active', true)->first();
                    if (!$origin) {
                        $fail('Sekolah asal dengan NPSN tersebut tidak ditemukan atau tidak aktif.');
                        return;
                    }
                    if ((int) $origin->id === (int) $targetInstitutionId) {
                        $fail('Sekolah asal harus berbeda dengan sekolah Anda.');
                    }
                },
            ],
            'origin_school_name' => 'required_if:external,true|nullable|string|max:255',
            'nik' => [
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]{16}$/',
                function ($attribute, $value, $fail) use ($external) {
                    if ($external) {
                        $existing = Employee::withTrashed()->where('nik', $value)->first();
                        if ($existing) {
                            $existing->loadMissing('institution:id,name');
                            $where = $existing->institution?->name ?: 'sekolah lain';
                            $trash = $existing->trashed() ? ' (kotak sampah)' : '';
                            $fail("NIK tersebut sudah terdaftar di {$where}{$trash}. Gunakan tarik guru, jangan input manual.");
                        }
                        return;
                    }
                    $originNpsn = $this->input('origin_npsn');
                    $origin = Institution::where('npsn', $originNpsn)->first();
                    if (!$origin) {
                        return;
                    }
                    try {
                        $employee = app(\App\Services\TeacherMutationService::class)
                            ->findPullableTeacherByNik($value, $origin->id);
                    } catch (\InvalidArgumentException $e) {
                        $fail($e->getMessage());
                        return;
                    }
                    $pending = TeacherMutation::where('employee_id', $employee->id)
                        ->pending()
                        ->exists();
                    if ($pending) {
                        $fail('Sudah ada permohonan mutasi guru ini yang belum diproses.');
                    }
                },
            ],
            'employee_name' => 'required_if:external,true|nullable|string|max:255',
            'employee_gender' => 'required_if:external,true|nullable|string|in:L,P,Laki-laki,Perempuan,Laki,Perempuan',
            'employee_nip' => 'nullable|string|max:32',
            'employee_nuptk' => 'nullable|string|max:32',
            'employee_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'origin_npsn.required' => 'NPSN sekolah asal wajib diisi.',
            'origin_npsn.size' => 'NPSN harus 8 digit.',
            'origin_npsn.regex' => 'NPSN harus berupa 8 digit angka.',
            'origin_school_name.required_if' => 'Nama sekolah asal wajib diisi untuk mutasi masuk dari sekolah luar sistem.',
            'nik.required' => 'NIK guru wajib diisi.',
            'nik.size' => 'NIK harus 16 digit.',
            'nik.regex' => 'NIK harus berupa 16 digit angka.',
            'employee_name.required_if' => 'Nama guru wajib diisi untuk mutasi masuk dari sekolah luar.',
            'employee_gender.required_if' => 'Jenis kelamin guru wajib diisi.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\TeacherMutation;
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
     * Get the validation rules that apply to the request.
     * User = admin sekolah TARGET (penarik). Body: origin_npsn (sekolah asal), nuptk (guru di sekolah asal).
     * Tidak ada batasan jenjang untuk mutasi guru.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $external = $this->boolean('external');

        return [
            'external' => 'sometimes|boolean',
            'origin_npsn' => [
                'required',
                'string',
                'size:8',
                'regex:/^[0-9]{8}$/',
                function ($attribute, $value, $fail) use ($external) {
                    if ($external) {
                        return;
                    }
                    $targetInstitution = Institution::find($this->user()->institution_id);
                    if (!$targetInstitution) {
                        $fail('Sekolah Anda tidak ditemukan.');
                        return;
                    }
                    $origin = Institution::where('npsn', $value)->where('is_active', true)->first();
                    if (!$origin) {
                        $fail('Sekolah asal dengan NPSN tersebut tidak ditemukan atau tidak aktif.');
                        return;
                    }
                    if ((int) $origin->id === (int) $targetInstitution->id) {
                        $fail('Sekolah asal harus berbeda dengan sekolah Anda.');
                    }
                },
            ],
            'origin_school_name' => 'required_if:external,true|nullable|string|max:255',
            'nuptk' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($external) {
                    $targetInstitutionId = $this->user()->institution_id;
                    if ($external) {
                        if (Employee::where('nuptk', $value)->exists()) {
                            $fail('NUPTK tersebut sudah digunakan oleh guru lain di sistem.');
                        }
                        return;
                    }
                    $originNpsn = $this->input('origin_npsn');
                    $origin = Institution::where('npsn', $originNpsn)->first();
                    if (!$origin) {
                        return;
                    }
                    $employee = Employee::where('nuptk', $value)
                        ->where('institution_id', $origin->id)
                        ->where('type', 'Guru')
                        ->where('status', 'Aktif')
                        ->first();
                    if (!$employee) {
                        $fail('Guru dengan NUPTK tersebut tidak ditemukan di sekolah asal atau status tidak aktif.');
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
            'nuptk.required' => 'NUPTK guru wajib diisi.',
            'employee_name.required_if' => 'Nama guru wajib diisi untuk mutasi masuk dari sekolah luar.',
            'employee_gender.required_if' => 'Jenis kelamin guru wajib diisi.',
        ];
    }
}

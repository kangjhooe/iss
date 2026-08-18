<?php

namespace App\Http\Requests;

use App\Models\TeacherChangeRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        $user->load(['teacherProfile', 'employeeProfile']);

        return ($user->teacherProfile || $user->employeeProfile) !== null;
    }

    public function rules(): array
    {
        $user = $this->user();
        $user->loadMissing(['teacherProfile', 'employeeProfile']);
        $employeeId = ($user->teacherProfile ?? $user->employeeProfile)?->id;

        $rules = [
            'field_name' => ['required', 'string', 'in:' . implode(',', TeacherChangeRequest::APPROVAL_FIELDS)],
            'new_value' => ['nullable', 'string', 'max:65535'],
        ];

        $fieldName = $this->input('field_name');

        switch ($fieldName) {
            case 'email':
                $rules['new_value'] = ['nullable', 'string', 'email', 'max:255'];
                break;
            case 'name':
                $rules['new_value'] = ['required', 'string', 'max:255'];
                break;
            case 'nik':
                $rules['new_value'] = [
                    'required',
                    'string',
                    'size:16',
                    'regex:/^[0-9]{16}$/',
                    Rule::unique('employee', 'nik')->ignore($employeeId),
                ];
                break;
            case 'nip':
                $rules['new_value'] = ['nullable', 'string', 'max:50'];
                break;
            case 'nuptk':
                $rules['new_value'] = [
                    'nullable',
                    'string',
                    'max:16',
                    Rule::unique('employee', 'nuptk')->ignore($employeeId),
                ];
                break;
            case 'gender':
                $rules['new_value'] = ['required', 'in:L,P'];
                break;
            case 'birth_date':
            case 'join_date':
            case 'certification_date':
                $rules['new_value'] = ['nullable', 'date'];
                break;
            case 'employment_status':
                $rules['new_value'] = [
                    'nullable',
                    'in:PNS,CPNS,Guru Tetap Yayasan,Guru Honor Sekolah,Guru Kontrak,Pegawai Tetap Yayasan,Pegawai Honor,Pegawai Kontrak',
                ];
                break;
            case 'status':
                $rules['new_value'] = [
                    'nullable',
                    'in:Aktif,Cuti,Pensiun,Pindah,Mengundurkan Diri,Tidak Aktif',
                ];
                break;
            case 'certification_status':
                $rules['new_value'] = ['nullable', 'in:Sudah,Belum'];
                break;
            case 'birth_place':
            case 'subject':
            case 'certification_issuing_authority':
                $rules['new_value'] = ['nullable', 'string', 'max:255'];
                break;
            case 'teacher_registration_number':
                $rules['new_value'] = ['nullable', 'string', 'max:50'];
                break;
            case 'certification_number':
                $rules['new_value'] = ['nullable', 'string', 'max:100'];
                break;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'field_name.required' => 'Field wajib dipilih',
            'field_name.in' => 'Field tidak diizinkan untuk diajukan perubahan',
            'new_value.required' => 'Nilai baru wajib diisi',
            'new_value.email' => 'Format email tidak valid',
            'new_value.date' => 'Format tanggal tidak valid (YYYY-MM-DD)',
            'new_value.size' => 'NIK harus 16 digit',
            'new_value.regex' => 'NIK harus berupa angka 16 digit',
            'new_value.unique' => 'Nilai sudah terdaftar pada pegawai lain',
            'new_value.in' => 'Nilai tidak valid untuk field ini',
        ];
    }
}

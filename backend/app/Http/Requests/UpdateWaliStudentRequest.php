<?php

namespace App\Http\Requests;

use App\Http\Rules\StudentIdentityNotTaken;
use App\Models\Student;
use App\Support\RegionAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWaliStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('nis') && is_string($this->input('nis')) && trim($this->input('nis')) === '') {
            $this->merge(['nis' => null]);
        }
    }

    public function rules(): array
    {
        $studentId = $this->studentId();
        $institutionId = $this->institutionId($studentId);

        return [
            'nik' => [
                'sometimes',
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]{16}$/',
                Rule::unique('student', 'nik')
                    ->ignore($studentId)
                    ->where(function ($query) use ($institutionId) {
                        return $query->where('institution_id', $institutionId);
                    }),
                new StudentIdentityNotTaken('nik', $studentId),
            ],
            'nis' => [
                'nullable',
                'string',
                'max:50',
                \App\Services\LocalNisService::uniqueRule($institutionId, $studentId),
            ],
            'nisn' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[0-9]{10}$/',
                Rule::unique('student', 'nisn')
                    ->ignore($studentId)
                    ->where(function ($query) use ($institutionId) {
                        return $query->where('institution_id', $institutionId);
                    }),
                new StudentIdentityNotTaken('nisn', $studentId),
            ],
            'name' => 'sometimes|required|string|max:255',
            'gender' => 'sometimes|required|in:L,P',
            'birth_date' => 'sometimes|required|date',
            'birth_place' => 'sometimes|required|string|max:255',
            ...RegionAddress::rules(),
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'religion' => 'nullable|string|max:50',
            'no_kk' => 'nullable|string|max:16',
            'aspiration' => 'nullable|string|max:255',
            'hobby' => 'nullable|string|max:255',
            'disability' => 'nullable|string|max:255',
            'height' => 'nullable|integer|min:0|max:300',
            'weight' => 'nullable|integer|min:0|max:500',
            'previous_school' => 'nullable|string|max:255',
            'previous_school_npsn' => 'nullable|string|max:20',
            'previous_school_address' => 'nullable|string',
            'residence_type' => 'nullable|in:asrama,kost_kontrak,tinggal_dengan_orang_tua,lainnya',
            'father_name' => 'nullable|string|max:255',
            'father_status' => 'nullable|in:masih_hidup,meninggal_dunia,tidak_diketahui',
            'father_nik' => 'nullable|string|max:16',
            'father_birth_place' => 'nullable|string|max:255',
            'father_birth_date' => 'nullable|date',
            'father_education' => 'nullable|string|max:255',
            'father_occupation' => 'nullable|string|max:255',
            'father_income' => 'nullable|numeric|min:0',
            'mother_name' => 'nullable|string|max:255',
            'mother_status' => 'nullable|in:masih_hidup,meninggal_dunia,tidak_diketahui',
            'mother_nik' => 'nullable|string|max:16',
            'mother_birth_place' => 'nullable|string|max:255',
            'mother_birth_date' => 'nullable|date',
            'mother_education' => 'nullable|string|max:255',
            'mother_occupation' => 'nullable|string|max:255',
            'mother_income' => 'nullable|numeric|min:0',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_type' => 'nullable|in:sama_dengan_ayah,sama_dengan_ibu,lainnya',
            'guardian_status' => 'nullable|in:masih_hidup,meninggal_dunia,tidak_diketahui',
            'guardian_nik' => 'nullable|string|max:16',
            'guardian_birth_place' => 'nullable|string|max:255',
            'guardian_birth_date' => 'nullable|date',
            'guardian_education' => 'nullable|string|max:255',
            'guardian_occupation' => 'nullable|string|max:255',
            'guardian_income' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi',
            'nik.size' => 'NIK harus terdiri dari 16 digit',
            'nik.regex' => 'NIK harus berupa angka 16 digit',
            'nik.unique' => 'NIK sudah terdaftar di institusi ini',
            'nisn.size' => 'NISN harus terdiri dari 10 digit',
            'nisn.regex' => 'NISN harus berupa angka 10 digit',
            'nis.unique' => 'NIS sudah terdaftar di institusi ini',
            'nisn.unique' => 'NISN sudah terdaftar di institusi ini',
            'name.required' => 'Nama siswa wajib diisi',
            'gender.required' => 'Jenis kelamin wajib diisi',
            'gender.in' => 'Jenis kelamin harus L atau P',
            'birth_date.required' => 'Tanggal lahir wajib diisi',
            'birth_place.required' => 'Tempat lahir wajib diisi',
            'email.email' => 'Format email tidak valid',
        ];
    }

    private function institutionId($studentId): ?int
    {
        if ($this->user() && ! $this->user()->isAdminOrSuperAdmin()) {
            return $this->user()->institution_id ? (int) $this->user()->institution_id : null;
        }

        $institutionId = Student::whereKey($studentId)->value('institution_id');

        return $institutionId ? (int) $institutionId : null;
    }

    private function studentId(): mixed
    {
        $id = $this->route('studentId') ?? $this->route('student') ?? $this->route('id');

        return $id instanceof Student ? $id->getKey() : $id;
    }
}

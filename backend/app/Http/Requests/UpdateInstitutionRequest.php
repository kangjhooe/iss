<?php

namespace App\Http\Requests;

use App\Http\Rules\NpsnReferensiRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInstitutionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $institutionId = $this->route('institution') ?? $this->route('id');
        $institution = is_numeric($institutionId) 
            ? \App\Models\Institution::find($institutionId)
            : $institutionId;
        
        // Super admin dan admin dapat mengubah instansi mana pun; admin sekolah hanya instansi sendiri
        if ($this->user()?->isAdminOrSuperAdmin()) {
            return true;
        }
        
        return $institution && $this->user()?->institution_id == $institution->id;
    }

    /**
     * Normalisasi input kosong agar edit bertahap (field opsional boleh kosong).
     */
    protected function prepareForValidation(): void
    {
        $nullableKeys = [
            'nss', 'level', 'address', 'village', 'sub_district', 'district', 'province',
            'province_code', 'district_code', 'postal_code', 'phone', 'email', 'website',
            'principal_name', 'principal_nip', 'description', 'vision', 'mission',
            'active_academic_year_id', 'active_semester_id', 'latitude', 'longitude', 'location_radius',
            'npsn', 'name', 'foundation_name', 'type',
            'admission_label',
        ];

        $merged = [];
        foreach ($nullableKeys as $key) {
            if (!$this->exists($key)) {
                continue;
            }
            $value = $this->input($key);
            if ($value === '' || (is_string($value) && trim($value) === '')) {
                $merged[$key] = null;
            }
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $institutionId = $this->route('institution') ?? $this->route('id');
        $institutionId = is_object($institutionId) ? $institutionId->id : $institutionId;
        $user = $this->user();
        
        $rules = [];
        
        // Only super admin can directly change name and npsn
        $isSuperAdmin = $user && method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : false;
        
        if ($isSuperAdmin) {
            // Edit bertahap: name/npsn boleh tidak dikirim atau kosong (nilai lama dipertahankan di controller)
            $rules['name'] = 'sometimes|nullable|string|max:255';
            $rules['npsn'] = [
                'nullable',
                'string',
                'size:8',
                'regex:/^[0-9]{8}$/',
                Rule::unique('institution', 'npsn')->ignore($institutionId),
                new NpsnReferensiRule(),
            ];
        } else {
            // For non-super admin, name and npsn cannot be changed directly
            // They need to use change request system
        }
        
        $baseRules = [
            'foundation_name' => 'nullable|string|max:255',
            'nss' => 'nullable|string|max:255',
            'level' => 'nullable|in:TK,SD,SMP,SMA,SMK,MA,MAK,MTs,MI,PAUD',
            'type' => 'sometimes|nullable|in:Negeri,Swasta',
            'address' => 'nullable|string',
            'village' => 'nullable|string|max:255',
            'sub_district' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'province_code' => 'nullable|string|max:2',
            'district_code' => 'nullable|string|max:2',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'principal_name' => 'nullable|string|max:255',
            'principal_nip' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'vision' => 'nullable|string',
            'mission' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'active_academic_year_id' => 'nullable|exists:academic_years,id',
            'active_semester_id' => 'nullable|exists:semesters,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_radius' => 'nullable|integer|min:10|max:5000',
            'teacher_appreciation_leaderboard_mode' => [
                'sometimes',
                'nullable',
                Rule::in(\App\Models\Institution::TEACHER_APPRECIATION_LEADERBOARD_MODES),
            ],
            'admission_label' => 'sometimes|nullable|string|max:50',
        ];
        
        return array_merge($rules, $baseRules);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'npsn.size' => 'NPSN harus terdiri dari 8 digit',
            'npsn.regex' => 'NPSN harus berupa angka 8 digit',
            'npsn.unique' => 'NPSN sudah terdaftar',
            'type.in' => 'Status institusi harus Negeri atau Swasta',
        ];
    }
}

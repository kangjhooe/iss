<?php

namespace App\Http\Requests;

use App\Services\NpsnValidationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstitutionChangeRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only institution admin can request changes
        return $this->user()?->isInstitutionAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $institutionId = $this->user()->institution_id;
        
        return [
            'field_name' => 'required|in:name,npsn',
            'new_value' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $fieldName = $this->input('field_name');
                    
                    if ($fieldName === 'npsn') {
                        if (strlen($value) !== 8 || !preg_match('/^[0-9]{8}$/', $value)) {
                            $fail('NPSN harus terdiri dari 8 digit angka');
                        }
                    } elseif ($fieldName === 'name') {
                        if (strlen($value) > 255) {
                            $fail('Nama sekolah maksimal 255 karakter');
                        }
                    }
                },
                function ($attribute, $value, $fail) use ($institutionId) {
                    $fieldName = $this->input('field_name');
                    $institution = \App\Models\Institution::find($institutionId);
                    
                    if (!$institution) {
                        $fail('Institusi tidak ditemukan');
                        return;
                    }
                    
                    // Check if new value is different from current value
                    if ($fieldName === 'name' && $value === $institution->name) {
                        $fail('Nama baru harus berbeda dengan nama saat ini');
                    } elseif ($fieldName === 'npsn' && $value === $institution->npsn) {
                        $fail('NPSN baru harus berbeda dengan NPSN saat ini');
                    }
                    
                    // Check if NPSN already exists (if changing NPSN)
                    if ($fieldName === 'npsn') {
                        $exists = \App\Models\Institution::where('npsn', $value)
                            ->where('id', '!=', $institutionId)
                            ->exists();
                        if ($exists) {
                            $fail('NPSN sudah terdaftar');
                        }
                    }
                },
                function ($attribute, $value, $fail) {
                    $fieldName = $this->input('field_name');
                    if ($fieldName !== 'npsn') {
                        return;
                    }
                    $svc = NpsnValidationService::fromConfig();
                    $normalized = $svc->normalizeNpsn($value);
                    if ($normalized === null) {
                        return;
                    }
                    if (! $svc->isValid($normalized)) {
                        $fail('NPSN tidak terdaftar di data referensi Kemendikbud. Pastikan NPSN benar dan sekolah masih aktif.');
                    }
                },
            ],
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
            'field_name.required' => 'Field name wajib diisi',
            'field_name.in' => 'Field name harus berupa name atau npsn',
            'new_value.required' => 'Nilai baru wajib diisi',
        ];
    }
}

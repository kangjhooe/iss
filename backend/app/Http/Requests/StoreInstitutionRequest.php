<?php

namespace App\Http\Requests;

use App\Http\Rules\NpsnReferensiRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInstitutionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admin can create institutions
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'npsn' => [
                'nullable',
                'string',
                'size:8',
                'regex:/^[0-9]{8}$/',
                'unique:institution,npsn',
                new NpsnReferensiRule(),
            ],
            'nss' => 'nullable|string|max:255',
            'level' => 'nullable|in:TK,SD,SMP,SMA,SMK,MA,MAK,MTs,MI,PAUD',
            'type' => 'required|in:Negeri,Swasta',
            'address' => 'nullable|string',
            'village' => 'nullable|string|max:255',
            'sub_district' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'principal_name' => 'nullable|string|max:255',
            'principal_nip' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_radius' => 'nullable|integer|min:10|max:5000',
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
            'name.required' => 'Nama institusi wajib diisi',
            'npsn.size' => 'NPSN harus terdiri dari 8 digit',
            'npsn.regex' => 'NPSN harus berupa angka 8 digit',
            'npsn.unique' => 'NPSN sudah terdaftar',
            'type.required' => 'Status institusi wajib diisi',
            'type.in' => 'Status institusi harus Negeri atau Swasta',
        ];
    }
}

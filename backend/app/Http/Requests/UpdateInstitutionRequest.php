<?php

namespace App\Http\Requests;

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
        
        // Admin can update any, institution admin can only update their own
        if ($this->user()?->isAdmin()) {
            return true;
        }
        
        return $institution && $this->user()?->institution_id == $institution->id;
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
            $rules['name'] = 'sometimes|required|string|max:255';
            $rules['npsn'] = [
                'nullable',
                'string',
                'size:8',
                'regex:/^[0-9]{8}$/',
                Rule::unique('institution', 'npsn')->ignore($institutionId),
            ];
        } else {
            // For non-super admin, name and npsn cannot be changed directly
            // They need to use change request system
        }
        
        $baseRules = [
            'nss' => 'nullable|string|max:255',
            'level' => 'nullable|in:TK,SD,SMP,SMA,SMK,MA,MAK,MTs,MI,PAUD',
            'type' => 'sometimes|required|in:Negeri,Swasta',
            'address' => 'nullable|string',
            'village' => 'nullable|string|max:255',
            'sub_district' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'principal_name' => 'nullable|string|max:255',
            'principal_nip' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'active_academic_year_id' => 'nullable|exists:academic_years,id',
            'active_semester_id' => 'nullable|exists:semesters,id',
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
            'type.required' => 'Status institusi wajib diisi',
            'type.in' => 'Status institusi harus Negeri atau Swasta',
        ];
    }
}

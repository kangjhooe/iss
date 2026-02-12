<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePpdbApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ppdb_channel_id' => 'sometimes|exists:ppdb_channels,id',
            'name' => 'sometimes|string|max:255',
            'nik' => 'nullable|string|max:20',
            'nisn' => 'nullable|string|max:20',
            'gender' => 'sometimes|in:L,P',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'religion' => 'nullable|string|max:50',
            'previous_school' => 'nullable|string|max:255',
            'previous_school_npsn' => 'nullable|string|max:20',
            'previous_school_address' => 'nullable|string',
            'father_name' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:50',
            'father_nik' => 'nullable|string|max:20',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:50',
            'mother_nik' => 'nullable|string|max:20',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:50',
            'guardian_relation' => 'nullable|string|max:100',
            'status' => 'nullable|in:draft,submitted,verification,verified,rejected,passed,reserve,failed,re_registration,converted,cancelled',
            'documents_verified' => 'nullable|boolean',
            'verification_notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}

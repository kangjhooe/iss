<?php

namespace App\Http\Requests;

use App\Http\Rules\NpsnReferensiRule;
use App\Http\Rules\UniqueNisnPerPpdbPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePpdbApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $applicant = $this->route('ppdb_applicant');
        $periodId = $applicant?->ppdb_period_id;
        $ignoreId = $applicant?->id;
        return [
            'ppdb_channel_id' => 'sometimes|exists:ppdb_channels,id',
            'name' => 'sometimes|string|max:255',
            'nik' => 'nullable|string|max:20',
            'nisn' => array_filter([
                'nullable',
                'string',
                'max:20',
                ($periodId && $this->filled('nisn')) ? new UniqueNisnPerPpdbPeriod((int) $periodId, (int) $ignoreId) : null,
            ]),
            'gender' => 'sometimes|in:L,P',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'religion' => 'nullable|string|max:50',
            'previous_school' => 'nullable|string|max:255',
            'previous_school_npsn' => [
                'nullable',
                'string',
                'max:20',
                Rule::when($this->filled('previous_school_npsn'), [
                    'size:8',
                    'regex:/^[0-9]{8}$/',
                    new NpsnReferensiRule(),
                ]),
            ],
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

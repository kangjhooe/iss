<?php

namespace App\Http\Requests;

use App\Http\Rules\NpsnReferensiRule;
use App\Http\Rules\UniqueNisnPerPpdbPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePpdbApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ppdb_period_id' => 'required|exists:ppdb_periods,id',
            'ppdb_channel_id' => 'required|exists:ppdb_channels,id',
            'name' => 'required|string|max:255',
            'nik' => 'nullable|string|max:20',
            'nisn' => array_filter([
                'nullable',
                'string',
                'max:20',
                $this->filled('nisn') ? new UniqueNisnPerPpdbPeriod((int) $this->input('ppdb_period_id')) : null,
            ]),
            'gender' => 'required|in:L,P',
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
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'ppdb_period_id.required' => 'Periode PPDB wajib dipilih.',
            'ppdb_channel_id.required' => 'Jalur pendaftaran wajib dipilih.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'previous_school_npsn.size' => 'NPSN sekolah asal harus 8 digit.',
            'previous_school_npsn.regex' => 'NPSN sekolah asal harus berupa 8 digit angka.',
        ];
    }
}

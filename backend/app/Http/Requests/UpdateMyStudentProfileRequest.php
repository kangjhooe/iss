<?php

namespace App\Http\Requests;

use App\Models\StudentChangeRequest;
use App\Support\RegionAddress;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMyStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    public function rules(): array
    {
        return array_merge(RegionAddress::rules(), [
            'address' => ['sometimes', 'nullable', 'string'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'religion' => ['sometimes', 'nullable', 'string', 'max:50'],
            'aspiration' => ['sometimes', 'nullable', 'string', 'max:255'],
            'hobby' => ['sometimes', 'nullable', 'string', 'max:255'],
            'disability' => ['sometimes', 'nullable', 'string', 'max:255'],
            'height' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:300'],
            'weight' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:500'],
            'previous_school' => ['sometimes', 'nullable', 'string', 'max:255'],
            'previous_school_npsn' => ['sometimes', 'nullable', 'string', 'max:20'],
            'previous_school_address' => ['sometimes', 'nullable', 'string'],
            'residence_type' => ['sometimes', 'nullable', 'in:asrama,kost_kontrak,tinggal_dengan_orang_tua,lainnya'],
            'father_status' => ['sometimes', 'nullable', 'in:masih_hidup,meninggal_dunia,tidak_diketahui'],
            'father_birth_place' => ['sometimes', 'nullable', 'string', 'max:255'],
            'father_birth_date' => ['sometimes', 'nullable', 'date'],
            'father_education' => ['sometimes', 'nullable', 'string', 'max:255'],
            'father_occupation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'father_income' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'mother_status' => ['sometimes', 'nullable', 'in:masih_hidup,meninggal_dunia,tidak_diketahui'],
            'mother_birth_place' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mother_birth_date' => ['sometimes', 'nullable', 'date'],
            'mother_education' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mother_occupation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mother_income' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'guardian_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'guardian_type' => ['sometimes', 'nullable', 'in:sama_dengan_ayah,sama_dengan_ibu,lainnya'],
            'guardian_status' => ['sometimes', 'nullable', 'in:masih_hidup,meninggal_dunia,tidak_diketahui'],
            'guardian_birth_place' => ['sometimes', 'nullable', 'string', 'max:255'],
            'guardian_birth_date' => ['sometimes', 'nullable', 'date'],
            'guardian_education' => ['sometimes', 'nullable', 'string', 'max:255'],
            'guardian_occupation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'guardian_income' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $allowed = StudentChangeRequest::SELF_EDITABLE_FIELDS;
            $keys = array_values(array_intersect(array_keys($this->all()), array_merge(
                $allowed,
                StudentChangeRequest::APPROVAL_FIELDS,
                ['institution_id', 'class_id', 'class', 'tingkat', 'status', 'academic_year_id', 'semester_id', 'graduation_year']
            )));
            $forbidden = array_values(array_diff($keys, $allowed));
            if ($forbidden !== []) {
                $validator->errors()->add(
                    'fields',
                    'Field tidak diizinkan untuk diubah langsung: ' . implode(', ', $forbidden)
                );
            }
            if (count(array_intersect(array_keys($this->all()), $allowed)) === 0) {
                $validator->errors()->add('fields', 'Tidak ada field yang diubah.');
            }
        });
    }
}

<?php

namespace App\Http\Requests;

use App\Models\TeacherChangeRequest;
use App\Support\RegionAddress;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMyTeacherProfileRequest extends FormRequest
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
        return array_merge(RegionAddress::rules(), [
            'address' => ['sometimes', 'nullable', 'string'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'religion' => ['sometimes', 'nullable', 'string', 'max:50'],
            'education_level' => ['sometimes', 'nullable', 'in:SMA,D3,S1,S2,S3'],
            'major' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $allowed = TeacherChangeRequest::SELF_EDITABLE_FIELDS;
            $keys = array_values(array_intersect(array_keys($this->all()), array_merge(
                $allowed,
                // Reject any non-editable employee fields that might be posted.
                TeacherChangeRequest::APPROVAL_FIELDS,
                ['institution_id', 'type']
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

    public function messages(): array
    {
        return [
            'education_level.in' => 'Jenjang pendidikan tidak valid',
            'phone.max' => 'No. HP maksimal 20 karakter',
        ];
    }
}

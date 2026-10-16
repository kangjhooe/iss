<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddExamPeriodSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'integer|exists:subjects,id',
            'duration_minutes' => 'nullable|integer|min:1|max:600',
        ];
    }

    public function messages(): array
    {
        return [
            'subject_ids.required' => 'Pilih minimal satu mata pelajaran.',
            'subject_ids.min' => 'Pilih minimal satu mata pelajaran.',
        ];
    }
}

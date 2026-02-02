<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CopyLessonScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_semester_id' => 'required|exists:semesters,id',
            'target_semester_id' => 'required|exists:semesters,id|different:source_semester_id',
            'class_id' => 'nullable|exists:class,id',
        ];
    }

    public function messages(): array
    {
        return [
            'source_semester_id.required' => 'Semester sumber wajib dipilih.',
            'target_semester_id.required' => 'Semester tujuan wajib dipilih.',
            'target_semester_id.different' => 'Semester tujuan harus berbeda dari semester sumber.',
        ];
    }
}

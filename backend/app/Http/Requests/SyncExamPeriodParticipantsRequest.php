<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncExamPeriodParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'integer|exists:class,id',
            'exam_ids' => 'nullable|array',
            'exam_ids.*' => 'integer|exists:exams,id',
        ];
    }

    public function messages(): array
    {
        return [
            'class_ids.required' => 'Pilih minimal satu kelas.',
            'class_ids.min' => 'Pilih minimal satu kelas.',
        ];
    }
}

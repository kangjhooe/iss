<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExamParticipantBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:student,id',
        ];
    }
}

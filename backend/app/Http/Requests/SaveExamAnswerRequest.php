<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveExamAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login_token' => 'required|string',
            'question_bank_id' => 'required|integer',
            'answer_text' => 'nullable|string|max:10000',
            'question_option_id' => 'nullable|integer',
            'selected_option_ids' => 'nullable|array',
            'selected_option_ids.*' => 'integer',
            'matching_answer' => 'nullable|array',
            'matching_answer.*.left_id' => 'nullable|string',
            'matching_answer.*.right_id' => 'nullable|string',
        ];
    }
}

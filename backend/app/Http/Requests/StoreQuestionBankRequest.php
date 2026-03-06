<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_soal_id' => 'nullable|exists:bank_soal,id',
            'subject_id' => 'required_unless:bank_soal_id,*|nullable|exists:subjects,id',
            'stimulus_id' => 'nullable|exists:question_stimuli,id',
            'type' => 'required|in:pg,isian,uraian,pg_kompleks,matching',
            'body' => 'required|string|max:50000',
            'weight' => 'numeric|min:0|max:100',
            'key_answer' => 'nullable|string|max:500',
            'options' => 'array',
            'options.*.option_key' => 'required_with:options|string|max:5',
            'options.*.body' => 'required_with:options|string|max:20000',
            'options.*.is_correct' => 'boolean',
            'options.*.option_weight' => 'nullable|numeric|min:0|max:100',
            'matching_data' => 'nullable|array',
            'matching_data.left' => 'required_with:matching_data|array',
            'matching_data.left.*.id' => 'nullable|string',
            'matching_data.left.*.text' => 'required|string|max:1000',
            'matching_data.right' => 'required_with:matching_data|array',
            'matching_data.right.*.id' => 'nullable|string',
            'matching_data.right.*.text' => 'required|string|max:1000',
            'matching_data.correct' => 'required_with:matching_data|array',
            'matching_data.correct.*.left_id' => 'nullable|string',
            'matching_data.correct.*.right_id' => 'nullable|string',
        ];
    }
}

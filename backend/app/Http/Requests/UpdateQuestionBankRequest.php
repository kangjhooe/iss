<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesQuestionBankPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateQuestionBankRequest extends FormRequest
{
    use ValidatesQuestionBankPayload;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_id' => 'sometimes|exists:subjects,id',
            'stimulus_id' => 'nullable|exists:question_stimuli,id',
            'type' => 'sometimes|in:pg,isian,uraian,pg_kompleks,matching',
            'body' => 'sometimes|string|max:50000',
            'weight' => 'numeric|min:0|max:100',
            'key_answer' => 'nullable|string|max:500',
            'key_answer_aliases' => 'nullable|array|max:20',
            'key_answer_aliases.*' => 'string|max:500',
            'options' => 'array',
            'options.*.id' => 'nullable|exists:question_options,id',
            'options.*.option_key' => 'required_with:options|string|max:5',
            'options.*.body' => 'required_with:options|string|max:20000',
            'options.*.is_correct' => 'boolean',
            'options.*.option_weight' => 'nullable|numeric|min:0|max:100',
            'matching_data' => 'nullable|array',
            'matching_data.left' => 'array',
            'matching_data.left.*.id' => 'nullable|string',
            'matching_data.left.*.text' => 'string|max:1000',
            'matching_data.right' => 'array',
            'matching_data.right.*.id' => 'nullable|string',
            'matching_data.right.*.text' => 'string|max:1000',
            'matching_data.correct' => 'array',
            'matching_data.correct.*.left_id' => 'nullable|string',
            'matching_data.correct.*.right_id' => 'nullable|string',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->addQuestionBankTypeRules($validator);
    }
}

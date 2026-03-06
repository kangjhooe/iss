<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionStimulusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('POST');
        return [
            'bank_soal_id' => $isCreate ? 'required|exists:bank_soal,id' : 'prohibited',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:50000',
            'type' => 'nullable|in:text,image',
        ];
    }
}

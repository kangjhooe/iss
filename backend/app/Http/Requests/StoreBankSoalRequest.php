<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankSoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institutionId = $this->user()?->institution_id;
        $uniqueRule = 'unique:bank_soal,code';
        if ($institutionId) {
            $uniqueRule = 'unique:bank_soal,code,NULL,id,institution_id,' . $institutionId;
        }

        return [
            'code' => ['required', 'string', 'max:50', $uniqueRule],
            'name' => 'nullable|string|max:200',
            'subject_id' => 'required|exists:subjects,id',
            'grade' => 'nullable|integer|min:1|max:12',
            'keterangan' => 'nullable|string|max:2000',
        ];
    }
}

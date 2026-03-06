<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankSoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bankSoal = $this->route('bank_soal');
        $uniqueRule = Rule::unique('bank_soal', 'code')->ignore($bankSoal?->id);
        $institutionId = $this->user()?->institution_id;
        if ($institutionId) {
            $uniqueRule->where('institution_id', $institutionId);
        }

        return [
            'code' => ['sometimes', 'string', 'max:50', $uniqueRule],
            'name' => 'nullable|string|max:200',
            'subject_id' => 'sometimes|exists:subjects,id',
            'grade' => 'nullable|integer|min:1|max:12',
            'keterangan' => 'nullable|string|max:2000',
        ];
    }
}

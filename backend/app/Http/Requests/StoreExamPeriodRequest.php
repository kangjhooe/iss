<?php

namespace App\Http\Requests;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamPeriodRequest extends FormRequest
{
    use ResolvesInstitution;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institutionId = $this->resolveInstitutionId($this);

        return [
            'code' => [
                'required',
                'string',
                'max:64',
                Rule::unique('exam_periods', 'code')->where('institution_id', $institutionId),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'window_start_at' => 'nullable|date',
            'window_end_at' => 'nullable|date|after_or_equal:window_start_at',
            'status' => 'nullable|in:draft,open,closed',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode gelombang wajib diisi.',
            'code.unique' => 'Kode gelombang sudah digunakan di institusi ini.',
            'name.required' => 'Nama gelombang wajib diisi.',
            'window_end_at.after_or_equal' => 'Jadwal selesai tidak boleh sebelum jadwal mulai.',
        ];
    }
}

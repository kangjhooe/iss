<?php

namespace App\Http\Requests;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Models\ExamPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamPeriodRequest extends FormRequest
{
    use ResolvesInstitution;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institutionId = $this->resolveInstitutionId($this);
        $period = $this->route('exam_period');
        $periodId = $period instanceof ExamPeriod ? $period->id : $period;

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                Rule::unique('exam_periods', 'code')
                    ->where('institution_id', $institutionId)
                    ->ignore($periodId),
            ],
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'window_start_at' => 'nullable|date',
            'window_end_at' => 'nullable|date|after_or_equal:window_start_at',
            'status' => 'nullable|in:draft,open,closed',
            'apply_schedule_to_exams' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Kode gelombang sudah digunakan di institusi ini.',
            'name.required' => 'Nama gelombang wajib diisi.',
            'window_end_at.after_or_equal' => 'Jadwal selesai tidak boleh sebelum jadwal mulai.',
        ];
    }
}

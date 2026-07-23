<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpsertGradeWeightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester_id' => 'required|integer|exists:semesters,id',
            'class_id' => 'required|integer|exists:class,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'weight_penilaian' => 'required|numeric|min:0|max:100',
            'weight_uts' => 'required|numeric|min:0|max:100',
            'weight_uas' => 'required|numeric|min:0|max:100',
            'assessment_count' => 'nullable|integer|min:1|max:100',
            'deadline_penilaian' => 'nullable|date',
            'deadline_uts' => 'nullable|date',
            'deadline_uas' => 'nullable|date',
            'deadline_nilai_akhir' => 'nullable|date',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $p = (float) $this->input('weight_penilaian', 0);
            $uts = (float) $this->input('weight_uts', 0);
            $uas = (float) $this->input('weight_uas', 0);
            $sum = round($p + $uts + $uas, 2);
            if (abs($sum - 100) > 0.01) {
                $validator->errors()->add(
                    'weight_penilaian',
                    'Jumlah bobot Penilaian + UTS + UAS harus 100% (saat ini ' . $sum . '%).'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'weight_penilaian.required' => 'Bobot penilaian wajib diisi.',
            'weight_uts.required' => 'Bobot UTS wajib diisi.',
            'weight_uas.required' => 'Bobot UAS wajib diisi.',
            'assessment_count.min' => 'Jumlah kolom penilaian minimal 1.',
            'assessment_count.max' => 'Jumlah kolom penilaian maksimal 100.',
        ];
    }
}

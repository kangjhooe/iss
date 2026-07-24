<?php

namespace App\Http\Requests;

use App\Support\InstitutionContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateFinanceInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institutionId = InstitutionContext::resolveForUser(
            $this->user(),
            $this,
            $this->get('institution_id')
        );

        return [
            'fee_type_id' => [
                'required',
                Rule::exists('finance_fee_types', 'id')->where(fn ($q) => $q->where('institution_id', $institutionId)),
            ],
            'title' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0.01',
            'due_date' => 'nullable|date',
            'period_label' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'class_id' => [
                'nullable',
                Rule::exists('class', 'id')->where(fn ($q) => $q->where('institution_id', $institutionId)),
            ],
            'student_ids' => 'nullable|array',
            'student_ids.*' => [
                'integer',
                Rule::exists('student', 'id')->where(fn ($q) => $q->where('institution_id', $institutionId)),
            ],
            'all_students' => 'nullable|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasStudents = !empty($this->input('student_ids'));
            $hasClass = !empty($this->input('class_id'));
            $allStudents = (bool) $this->boolean('all_students');
            if (!$hasStudents && !$hasClass && !$allStudents) {
                $validator->errors()->add('target', 'Pilih target: siswa, kelas, atau semua siswa aktif.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'fee_type_id.required' => 'Jenis biaya wajib dipilih.',
            'fee_type_id.exists' => 'Jenis biaya tidak ditemukan.',
            'amount.min' => 'Nominal harus lebih dari 0.',
            'target' => 'Pilih target: siswa, kelas, atau semua siswa aktif.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\FinanceFeeType;
use App\Support\InstitutionContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinanceFeeTypeRequest extends FormRequest
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
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('finance_fee_types', 'code')->where(fn ($q) => $q->where('institution_id', $institutionId)),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency' => ['required', Rule::in(FinanceFeeType::FREQUENCIES)],
            'scope' => ['required', Rule::in(FinanceFeeType::SCOPES)],
            'default_amount' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama jenis biaya wajib diisi.',
            'frequency.required' => 'Frekuensi wajib dipilih.',
            'frequency.in' => 'Frekuensi tidak valid.',
            'scope.required' => 'Cakupan wajib dipilih.',
            'scope.in' => 'Cakupan tidak valid.',
            'code.unique' => 'Kode jenis biaya sudah dipakai.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\FinanceFeeType;
use App\Support\InstitutionContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinanceFeeTypeRequest extends FormRequest
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
        $feeTypeId = $this->route('fee_type')?->id ?? $this->route('fee_type');

        return [
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('finance_fee_types', 'code')
                    ->where(fn ($q) => $q->where('institution_id', $institutionId))
                    ->ignore($feeTypeId),
            ],
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'frequency' => ['sometimes', 'required', Rule::in(FinanceFeeType::FREQUENCIES)],
            'scope' => ['sometimes', 'required', Rule::in(FinanceFeeType::SCOPES)],
            'default_amount' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}

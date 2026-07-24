<?php

namespace App\Http\Requests;

use App\Models\FinancePayment;
use App\Support\InstitutionContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancePaymentRequest extends FormRequest
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
            'invoice_id' => [
                'required',
                Rule::exists('finance_invoices', 'id')->where(fn ($q) => $q->where('institution_id', $institutionId)),
            ],
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'nullable|date',
            'method' => ['nullable', Rule::in(FinancePayment::METHODS)],
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_id.required' => 'Tagihan wajib dipilih.',
            'invoice_id.exists' => 'Tagihan tidak ditemukan.',
            'amount.required' => 'Nominal pembayaran wajib diisi.',
            'amount.min' => 'Nominal pembayaran harus lebih dari 0.',
        ];
    }
}

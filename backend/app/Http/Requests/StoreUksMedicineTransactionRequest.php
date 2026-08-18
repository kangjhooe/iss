<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUksMedicineTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uks_medicine_id' => 'required|integer|exists:uks_medicines,id',
            'type' => ['required', 'string', Rule::in(['masuk', 'keluar', 'penyesuaian'])],
            'quantity' => 'required|integer|min:0',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
            'uks_visit_id' => 'nullable|integer|exists:uks_visits,id',
        ];
    }

    public function messages(): array
    {
        return [
            'uks_medicine_id.required' => 'Obat wajib dipilih.',
            'type.required' => 'Jenis transaksi wajib diisi.',
            'type.in' => 'Jenis transaksi harus masuk, keluar, atau penyesuaian.',
            'quantity.required' => 'Jumlah wajib diisi.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
        ];
    }
}

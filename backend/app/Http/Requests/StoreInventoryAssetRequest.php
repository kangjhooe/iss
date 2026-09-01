<?php

namespace App\Http\Requests;

use App\Support\InventoryCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institutionId = $this->input('institution_id') ?? $this->user()?->institution_id;

        return [
            'item_id' => 'required|exists:inventory_item,id',
            'count' => 'nullable|integer|min:1|max:100',
            'serial_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('inventory_asset', 'serial_number')->where(function ($q) use ($institutionId) {
                    if ($institutionId) {
                        $q->where('institution_id', $institutionId);
                    }

                    return $q->whereNotNull('serial_number');
                }),
            ],
            'registration_number' => 'nullable|string|max:100',
            'inventory_number' => 'nullable|string|max:100',
            'condition' => 'nullable|in:Baik,Rusak Ringan,Rusak Berat,Habis Pakai',
            'status' => 'nullable|in:Tersedia,Dipinjam,Rusak,Hilang,Dijual',
            'room_id' => 'nullable|exists:room,id',
            'building_id' => 'nullable|exists:building,id',
            'responsible_employee_id' => 'nullable|exists:employee,id',
            'location_note' => 'nullable|string',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
        ];
    }
}

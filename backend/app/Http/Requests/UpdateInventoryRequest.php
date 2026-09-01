<?php

namespace App\Http\Requests;

use App\Helpers\FileUploadRules;
use App\Support\InventoryCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $item = $this->route('item');
        $itemId = $item ? $item->id : null;
        $institutionId = $item?->institution_id ?? $this->user()?->institution_id;

        $rules = [
            'category_id' => 'sometimes|exists:inventory_category,id',
            'tracking_type' => ['sometimes', Rule::in(InventoryCatalog::trackingTypes())],
            'code' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('inventory_item', 'code')
                    ->ignore($itemId)
                    ->where(fn ($q) => $institutionId ? $q->where('institution_id', $institutionId) : $q),
            ],
            'master_code' => 'nullable|string|max:100',
            'name' => 'sometimes|string|max:255',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('inventory_item', 'serial_number')
                    ->ignore($itemId)
                    ->where(function ($q) use ($institutionId) {
                        if ($institutionId) {
                            $q->where('institution_id', $institutionId);
                        }

                        return $q->whereNotNull('serial_number');
                    }),
            ],
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'additional_cost' => 'nullable|numeric|min:0',
            'book_value' => 'nullable|numeric|min:0',
            'valuation_notes' => 'nullable|string',
            'supplier' => 'nullable|string|max:255',
            'acquisition_method' => ['nullable', Rule::in(InventoryCatalog::acquisitionMethods())],
            'funding_source' => 'nullable|string|max:100',
            'ownership_type' => ['nullable', Rule::in(InventoryCatalog::ownershipTypes())],
            'owner_name' => 'nullable|string|max:255',
            'ownership_document_number' => 'nullable|string|max:100',
            'ownership_date' => 'nullable|date',
            'ownership_notes' => 'nullable|string',
            'condition' => 'sometimes|in:Baik,Rusak Ringan,Rusak Berat,Habis Pakai',
            'status' => 'sometimes|in:Tersedia,Dipinjam,Rusak,Hilang,Dijual',
            'unit' => 'nullable|string|max:50',
            'room_id' => 'nullable|exists:room,id',
            'building_id' => 'nullable|exists:building,id',
            'responsible_employee_id' => 'nullable|exists:employee,id',
            'location_note' => 'nullable|string',
            'warranty_expiry' => 'nullable|date',
            'description' => 'nullable|string',
        ];

        return array_merge($rules, FileUploadRules::inventoryImage());
    }
}

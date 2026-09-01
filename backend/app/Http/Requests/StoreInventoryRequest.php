<?php

namespace App\Http\Requests;

use App\Helpers\FileUploadRules;
use App\Support\InventoryCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institutionId = $this->resolveInstitutionId();

        $rules = [
            'category_id' => 'required|exists:inventory_category,id',
            'tracking_type' => ['nullable', Rule::in(InventoryCatalog::trackingTypes())],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('inventory_item', 'code')->where(fn ($q) => $institutionId ? $q->where('institution_id', $institutionId) : $q),
            ],
            'custom_code' => 'nullable|string|max:20',
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('inventory_item', 'serial_number')->where(function ($q) use ($institutionId) {
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
            'condition' => 'required|in:Baik,Rusak Ringan,Rusak Berat,Habis Pakai',
            'status' => 'nullable|in:Tersedia,Dipinjam,Rusak,Hilang,Dijual',
            'quantity' => 'required|integer|min:1|max:500',
            'unit' => 'nullable|string|max:50',
            'room_id' => 'nullable|exists:room,id',
            'building_id' => 'nullable|exists:building,id',
            'responsible_employee_id' => 'nullable|exists:employee,id',
            'location_note' => 'nullable|string',
            'warranty_expiry' => 'nullable|date',
            'description' => 'nullable|string',
            'reference_number' => 'nullable|string|max:255',
        ];

        return array_merge($rules, FileUploadRules::inventoryImage());
    }

    protected function resolveInstitutionId(): ?int
    {
        $user = $this->user();
        if (! $user) {
            return null;
        }
        if ($user->isAdminOrSuperAdmin()) {
            $id = $this->input('institution_id');

            return $id !== null && $id !== '' ? (int) $id : ($user->institution_id ? (int) $user->institution_id : null);
        }

        return $user->institution_id ? (int) $user->institution_id : null;
    }
}

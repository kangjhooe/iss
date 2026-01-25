<?php

namespace App\Http\Requests;

use App\Helpers\FileUploadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $itemId = $this->route('item') ? $this->route('item')->id : null;

        $rules = [
            'category_id' => 'sometimes|exists:inventory_category,id',
            'code' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('inventory_item', 'code')->ignore($itemId),
            ],
            'name' => 'sometimes|string|max:255',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('inventory_item', 'serial_number')->ignore($itemId),
            ],
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'condition' => 'sometimes|in:Baik,Rusak Ringan,Rusak Berat,Habis Pakai',
            'status' => 'sometimes|in:Tersedia,Dipinjam,Rusak,Hilang,Dijual',
            'quantity' => 'sometimes|integer|min:1',
            'unit' => 'nullable|string|max:50',
            'room_id' => 'nullable|exists:room,id',
            'building_id' => 'nullable|exists:building,id',
            'location_note' => 'nullable|string',
            'warranty_expiry' => 'nullable|date',
            'description' => 'nullable|string',
        ];

        // Add image upload rules using helper
        $rules = array_merge($rules, FileUploadRules::inventoryImage());

        return $rules;
    }
}

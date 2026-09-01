<?php

namespace App\Http\Requests;

use App\Models\InventoryAsset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var InventoryAsset|null $asset */
        $asset = $this->route('asset');
        $assetId = $asset?->id;
        $institutionId = $asset?->institution_id;

        return [
            'serial_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('inventory_asset', 'serial_number')
                    ->ignore($assetId)
                    ->where(function ($q) use ($institutionId) {
                        if ($institutionId) {
                            $q->where('institution_id', $institutionId);
                        }

                        return $q->whereNotNull('serial_number');
                    }),
            ],
            'registration_number' => 'nullable|string|max:100',
            'inventory_number' => 'nullable|string|max:100',
            'condition' => 'sometimes|in:Baik,Rusak Ringan,Rusak Berat,Habis Pakai',
            'status' => 'sometimes|in:Tersedia,Dipinjam,Rusak,Hilang,Dijual',
            'room_id' => 'nullable|exists:room,id',
            'building_id' => 'nullable|exists:building,id',
            'responsible_employee_id' => 'nullable|exists:employee,id',
            'location_note' => 'nullable|string',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'location_start_date' => 'nullable|date',
            'responsible_start_date' => 'nullable|date',
        ];
    }
}

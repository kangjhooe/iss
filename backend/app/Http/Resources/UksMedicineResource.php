<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UksMedicineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'name' => $this->name,
            'code' => $this->code,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'min_stock' => $this->min_stock,
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_low_stock' => $this->isLowStock(),
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

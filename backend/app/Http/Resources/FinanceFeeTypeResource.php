<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceFeeTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'frequency' => $this->frequency,
            'scope' => $this->scope,
            'default_amount' => (float) $this->default_amount,
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
            'invoices_count' => $this->when(isset($this->invoices_count), $this->invoices_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

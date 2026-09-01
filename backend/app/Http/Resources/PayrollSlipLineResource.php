<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollSlipLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slip_id' => $this->slip_id,
            'component_id' => $this->component_id,
            'label' => $this->label,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'is_manual_override' => (bool) $this->is_manual_override,
            'source' => $this->source,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'item_id' => $this->item_id,
            'transaction_type' => $this->transaction_type,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'quantity' => $this->quantity,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'item' => $this->when($this->relationLoaded('item'), function () {
                return [
                    'id' => $this->item->id ?? null,
                    'code' => $this->item->code ?? null,
                    'name' => $this->item->name ?? null,
                    'category' => $this->item->category ? [
                        'id' => $this->item->category->id,
                        'name' => $this->item->category->name,
                    ] : null,
                ];
            }),
            'from_location' => $this->when($this->relationLoaded('fromLocation'), function () {
                return $this->fromLocation ? [
                    'id' => $this->fromLocation->id,
                    'name' => $this->fromLocation->name,
                    'code' => $this->fromLocation->code,
                ] : null;
            }),
            'to_location' => $this->when($this->relationLoaded('toLocation'), function () {
                return $this->toLocation ? [
                    'id' => $this->toLocation->id,
                    'name' => $this->toLocation->name,
                    'code' => $this->toLocation->code,
                ] : null;
            }),
            'creator' => $this->when($this->relationLoaded('creator'), function () {
                return [
                    'id' => $this->creator->id ?? null,
                    'name' => $this->creator->name ?? null,
                    'email' => $this->creator->email ?? null,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

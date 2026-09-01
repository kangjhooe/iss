<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAssetMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'asset_id' => $this->asset_id,
            'item_id' => $this->item_id,
            'movement_date' => $this->movement_date?->format('Y-m-d'),
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'asset' => $this->whenLoaded('asset', fn () => $this->asset ? [
                'id' => $this->asset->id,
                'asset_number' => $this->asset->asset_number,
                'serial_number' => $this->asset->serial_number,
            ] : null),
            'item' => $this->whenLoaded('item', fn () => $this->item ? [
                'id' => $this->item->id,
                'code' => $this->item->code,
                'name' => $this->item->name,
            ] : null),
            'from_room' => $this->whenLoaded('fromRoom', fn () => $this->fromRoom ? [
                'id' => $this->fromRoom->id,
                'name' => $this->fromRoom->name,
            ] : null),
            'to_room' => $this->whenLoaded('toRoom', fn () => $this->toRoom ? [
                'id' => $this->toRoom->id,
                'name' => $this->toRoom->name,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

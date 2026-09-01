<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAssetOpnameLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'opname_id' => $this->opname_id,
            'asset_id' => $this->asset_id,
            'book_status' => $this->book_status,
            'book_condition' => $this->book_condition,
            'found' => $this->found,
            'counted_condition' => $this->counted_condition,
            'notes' => $this->notes,
            'asset' => $this->whenLoaded('asset', fn () => $this->asset ? [
                'id' => $this->asset->id,
                'asset_number' => $this->asset->asset_number,
                'serial_number' => $this->asset->serial_number,
                'status' => $this->asset->status,
                'condition' => $this->asset->condition,
                'item' => $this->asset->relationLoaded('item') && $this->asset->item ? [
                    'id' => $this->asset->item->id,
                    'code' => $this->asset->item->code,
                    'name' => $this->asset->item->name,
                ] : null,
                'room' => $this->asset->relationLoaded('room') && $this->asset->room ? [
                    'id' => $this->asset->room->id,
                    'name' => $this->asset->room->name,
                ] : null,
            ] : null),
        ];
    }
}

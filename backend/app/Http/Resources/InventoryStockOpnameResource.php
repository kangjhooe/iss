<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryStockOpnameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'opname_number' => $this->opname_number,
            'opname_date' => $this->opname_date?->format('Y-m-d'),
            'room_id' => $this->room_id,
            'building_id' => $this->building_id,
            'status' => $this->status,
            'opname_type' => $this->opname_type ?? 'stock',
            'notes' => $this->notes,
            'finalized_at' => $this->finalized_at?->format('Y-m-d H:i:s'),
            'lines_count' => $this->isAssetOpname()
                ? ($this->asset_lines_count ?? ($this->relationLoaded('assetLines') ? $this->assetLines->count() : null))
                : ($this->stock_lines_count ?? ($this->relationLoaded('lines') ? $this->lines->count() : null)),
            'counted_lines' => $this->isAssetOpname()
                ? ($this->counted_asset_lines_count ?? ($this->relationLoaded('assetLines') ? $this->assetLines->whereNotNull('found')->count() : null))
                : ($this->counted_stock_lines_count ?? ($this->relationLoaded('lines') ? $this->lines->whereNotNull('counted_quantity')->count() : null)),
            'variance_lines' => ! $this->isAssetOpname() && $this->relationLoaded('lines')
                ? $this->lines->filter(fn ($l) => (int) $l->variance !== 0)->count()
                : null,
            'missing_lines' => $this->isAssetOpname() && $this->relationLoaded('assetLines')
                ? $this->assetLines->where('found', false)->count()
                : null,
            'room' => $this->whenLoaded('room', fn () => $this->room ? [
                'id' => $this->room->id,
                'name' => $this->room->name,
            ] : null),
            'building' => $this->whenLoaded('building', fn () => $this->building ? [
                'id' => $this->building->id,
                'name' => $this->building->name,
            ] : null),
            'lines' => InventoryStockOpnameLineResource::collection($this->whenLoaded('lines')),
            'asset_lines' => InventoryAssetOpnameLineResource::collection($this->whenLoaded('assetLines')),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

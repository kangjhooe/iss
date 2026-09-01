<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMaintenanceResource extends JsonResource
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
            'asset_id' => $this->asset_id,
            'maintenance_type' => $this->maintenance_type,
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'completed_date' => $this->completed_date?->format('Y-m-d'),
            'cost' => $this->cost,
            'vendor' => $this->vendor,
            'description' => $this->description,
            'status' => $this->status,
            'technician_name' => $this->technician_name,
            'notes' => $this->notes,
            'item' => $this->when($this->relationLoaded('item'), function () {
                return [
                    'id' => $this->item->id ?? null,
                    'code' => $this->item->code ?? null,
                    'name' => $this->item->name ?? null,
                    'tracking_type' => $this->item->tracking_type ?? 'stock',
                    'category' => $this->item->category ? [
                        'id' => $this->item->category->id,
                        'name' => $this->item->category->name,
                    ] : null,
                ];
            }),
            'asset' => $this->when($this->relationLoaded('asset'), function () {
                return $this->asset ? [
                    'id' => $this->asset->id,
                    'asset_number' => $this->asset->asset_number,
                    'serial_number' => $this->asset->serial_number,
                ] : null;
            }),
            'creator' => $this->when($this->relationLoaded('creator'), function () {
                return [
                    'id' => $this->creator->id ?? null,
                    'name' => $this->creator->name ?? null,
                    'email' => $this->creator->email ?? null,
                ];
            }),
            'updater' => $this->when($this->relationLoaded('updater'), function () {
                return [
                    'id' => $this->updater->id ?? null,
                    'name' => $this->updater->name ?? null,
                    'email' => $this->updater->email ?? null,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

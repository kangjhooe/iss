<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'item_id' => $this->item_id,
            'asset_number' => $this->asset_number,
            'inventory_number' => $this->inventory_number,
            'serial_number' => $this->serial_number,
            'registration_number' => $this->registration_number,
            'qr_token' => $this->qr_token,
            'condition' => $this->condition,
            'status' => $this->status,
            'disposal_status' => $this->disposal_status,
            'location_note' => $this->location_note,
            'description' => $this->description,
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'purchase_price' => $this->purchase_price,
            'location_start_date' => $this->location_start_date?->format('Y-m-d'),
            'responsible_start_date' => $this->responsible_start_date?->format('Y-m-d'),
            'disposed_at' => $this->disposed_at?->format('Y-m-d'),
            'disposal_reason' => $this->disposal_reason,
            'disposal_document_number' => $this->disposal_document_number,
            'item' => $this->when($this->relationLoaded('item'), function () {
                return [
                    'id' => $this->item->id ?? null,
                    'code' => $this->item->code ?? null,
                    'master_code' => $this->item->master_code ?? null,
                    'name' => $this->item->name ?? null,
                    'tracking_type' => $this->item->tracking_type ?? null,
                    'category' => $this->item->category ? [
                        'id' => $this->item->category->id,
                        'name' => $this->item->category->name,
                    ] : null,
                ];
            }),
            'room' => $this->when($this->relationLoaded('room'), fn () => [
                'id' => $this->room->id ?? null,
                'name' => $this->room->name ?? null,
            ]),
            'building' => $this->when($this->relationLoaded('building'), fn () => [
                'id' => $this->building->id ?? null,
                'name' => $this->building->name ?? null,
            ]),
            'responsible_employee' => $this->when($this->relationLoaded('responsibleEmployee'), fn () => [
                'id' => $this->responsibleEmployee->id ?? null,
                'name' => $this->responsibleEmployee->name ?? null,
                'nip' => $this->responsibleEmployee->nip ?? null,
            ]),
            'creator' => $this->when($this->relationLoaded('creator'), fn () => [
                'id' => $this->creator->id ?? null,
                'name' => $this->creator->name ?? null,
            ]),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

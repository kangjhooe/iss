<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class InventoryDisposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'item_id' => $this->item_id,
            'asset_id' => $this->asset_id,
            'transaction_id' => $this->transaction_id,
            'quantity' => $this->quantity,
            'disposal_date' => $this->disposal_date?->format('Y-m-d'),
            'status' => $this->status,
            'disposal_reason' => $this->disposal_reason,
            'disposal_document_number' => $this->disposal_document_number,
            'document_name' => $this->document_name,
            'document_size' => $this->document_size,
            'document_mime' => $this->document_mime,
            'document_url' => $this->document_path
                ? Storage::disk('public')->url($this->document_path)
                : null,
            'has_document' => (bool) $this->document_path,
            'item' => $this->whenLoaded('item', fn () => [
                'id' => $this->item->id,
                'code' => $this->item->code,
                'name' => $this->item->name,
                'unit' => $this->item->unit,
                'quantity' => $this->item->quantity,
                'tracking_type' => $this->item->tracking_type ?? 'stock',
                'disposed_at' => $this->item->disposed_at?->format('Y-m-d'),
            ]),
            'asset' => $this->whenLoaded('asset', fn () => $this->asset ? [
                'id' => $this->asset->id,
                'asset_number' => $this->asset->asset_number,
                'serial_number' => $this->asset->serial_number,
            ] : null),
            'asset_number' => $this->asset?->asset_number,
            'item_code' => $this->item?->code,
            'item_name' => $this->item?->name,
            'item_unit' => $this->item?->unit,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

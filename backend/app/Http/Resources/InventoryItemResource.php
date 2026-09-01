<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
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
            'category_id' => $this->category_id,
            'tracking_type' => $this->tracking_type ?? 'stock',
            'identity_status' => $this->identity_status ?? 'complete',
            'code' => $this->code,
            'master_code' => $this->master_code,
            'legacy_code' => $this->legacy_code,
            'name' => $this->name,
            'brand' => $this->brand,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'purchase_price' => $this->purchase_price,
            'additional_cost' => $this->additional_cost,
            'book_value' => $this->book_value,
            'valuation_notes' => $this->valuation_notes,
            'supplier' => $this->supplier,
            'acquisition_method' => $this->acquisition_method,
            'funding_source' => $this->funding_source,
            'ownership_type' => $this->ownership_type,
            'owner_name' => $this->owner_name,
            'ownership_document_number' => $this->ownership_document_number,
            'ownership_date' => $this->ownership_date?->format('Y-m-d'),
            'ownership_notes' => $this->ownership_notes,
            'condition' => $this->condition,
            'status' => $this->status,
            'quantity' => $this->quantity,
            'available_quantity' => $this->getAvailableQuantity(),
            'unit' => $this->unit,
            'warranty_expiry' => $this->warranty_expiry?->format('Y-m-d'),
            'description' => $this->description,
            'location_note' => $this->location_note,
            'disposed_at' => $this->disposed_at?->format('Y-m-d'),
            'disposal_reason' => $this->disposal_reason,
            'disposal_document_number' => $this->disposal_document_number,
            'image_url' => $this->image_path ? asset('storage/' . $this->image_path) : null,
            'category' => $this->when($this->relationLoaded('category'), function () {
                return [
                    'id' => $this->category->id ?? null,
                    'code' => $this->category->code ?? null,
                    'name' => $this->category->name ?? null,
                ];
            }),
            'room' => $this->when($this->relationLoaded('room'), function () {
                return [
                    'id' => $this->room->id ?? null,
                    'name' => $this->room->name ?? null,
                    'code' => $this->room->code ?? null,
                ];
            }),
            'building' => $this->when($this->relationLoaded('building'), function () {
                return [
                    'id' => $this->building->id ?? null,
                    'name' => $this->building->name ?? null,
                    'code' => $this->building->code ?? null,
                ];
            }),
            'responsible_employee' => $this->when($this->relationLoaded('responsibleEmployee'), function () {
                return [
                    'id' => $this->responsibleEmployee->id ?? null,
                    'name' => $this->responsibleEmployee->name ?? null,
                    'nip' => $this->responsibleEmployee->nip ?? null,
                ];
            }),
            'institution' => $this->when($this->relationLoaded('institution'), function () {
                return [
                    'id' => $this->institution->id ?? null,
                    'name' => $this->institution->name ?? null,
                    'npsn' => $this->institution->npsn ?? null,
                ];
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
            'transactions_count' => $this->whenLoaded('transactions', fn() => $this->transactions->count()),
            'maintenances_count' => $this->whenLoaded('maintenances', fn() => $this->maintenances->count()),
            'loans_count' => $this->whenLoaded('loans', fn() => $this->loans->count()),
            'assets_count' => $this->whenLoaded('assets', fn () => $this->assets->count()),
            'transactions' => InventoryTransactionResource::collection($this->whenLoaded('transactions')),
            'maintenances' => InventoryMaintenanceResource::collection($this->whenLoaded('maintenances')),
            'loans' => InventoryLoanResource::collection($this->whenLoaded('loans')),
            'assets' => InventoryAssetResource::collection($this->whenLoaded('assets')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

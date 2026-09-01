<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLoanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $borrowerName = $this->borrower_name;
        if ($this->borrower_type === 'Employee' && $this->relationLoaded('borrowerEmployee') && $this->borrowerEmployee) {
            $borrowerName = $this->borrowerEmployee->name;
        } elseif ($this->borrower_type === 'Student' && $this->relationLoaded('borrowerStudent') && $this->borrowerStudent) {
            $borrowerName = $this->borrowerStudent->name;
        }

        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'item_id' => $this->item_id,
            'asset_id' => $this->asset_id,
            'borrower_type' => $this->borrower_type,
            'borrower_id' => $this->borrower_id,
            'borrower_name' => $borrowerName,
            'borrower_phone' => $this->borrower_phone,
            'loan_date' => $this->loan_date?->format('Y-m-d'),
            'expected_return_date' => $this->expected_return_date?->format('Y-m-d'),
            'actual_return_date' => $this->actual_return_date?->format('Y-m-d'),
            'quantity' => $this->quantity,
            'purpose' => $this->purpose,
            'status' => $this->status,
            'notes' => $this->notes,
            'return_condition' => $this->return_condition,
            'return_item_status' => $this->return_item_status,
            'is_overdue' => $this->isOverdue() || $this->status === 'Terlambat',
            'asset' => $this->when($this->relationLoaded('asset') && $this->asset, function () {
                return [
                    'id' => $this->asset->id,
                    'asset_number' => $this->asset->asset_number,
                    'inventory_number' => $this->asset->inventory_number,
                    'serial_number' => $this->asset->serial_number,
                ];
            }),
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
            'borrower_employee' => $this->when($this->relationLoaded('borrowerEmployee'), function () {
                return $this->borrowerEmployee ? [
                    'id' => $this->borrowerEmployee->id,
                    'name' => $this->borrowerEmployee->name,
                    'nip' => $this->borrowerEmployee->nip,
                ] : null;
            }),
            'borrower_student' => $this->when($this->relationLoaded('borrowerStudent'), function () {
                return $this->borrowerStudent ? [
                    'id' => $this->borrowerStudent->id,
                    'name' => $this->borrowerStudent->name,
                    'nis' => $this->borrowerStudent->nis,
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

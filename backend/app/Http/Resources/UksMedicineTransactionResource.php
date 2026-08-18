<?php

namespace App\Http\Resources;

use App\Models\UksMedicineTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UksMedicineTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'uks_medicine_id' => $this->uks_medicine_id,
            'type' => $this->type,
            'type_label' => UksMedicineTransaction::TYPES[$this->type] ?? $this->type,
            'quantity' => $this->quantity,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'uks_visit_id' => $this->uks_visit_id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'medicine' => $this->whenLoaded('medicine', fn () => $this->medicine ? [
                'id' => $this->medicine->id,
                'name' => $this->medicine->name,
                'code' => $this->medicine->code,
                'unit' => $this->medicine->unit,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'visit' => $this->whenLoaded('visit', fn () => $this->visit ? [
                'id' => $this->visit->id,
                'visit_date' => $this->visit->visit_date?->format('Y-m-d'),
                'student_id' => $this->visit->student_id,
            ] : null),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'invoice_id' => $this->invoice_id,
            'invoice' => $this->whenLoaded('invoice', fn () => [
                'id' => $this->invoice->id,
                'title' => $this->invoice->title,
                'status' => $this->invoice->status,
                'amount' => (float) $this->invoice->amount,
                'amount_paid' => (float) $this->invoice->amount_paid,
                'student' => $this->invoice->relationLoaded('student') && $this->invoice->student ? [
                    'id' => $this->invoice->student->id,
                    'name' => $this->invoice->student->name,
                    'nis' => $this->invoice->student->nis,
                ] : null,
                'fee_type' => $this->invoice->relationLoaded('feeType') && $this->invoice->feeType ? [
                    'id' => $this->invoice->feeType->id,
                    'name' => $this->invoice->feeType->name,
                ] : null,
                'class' => $this->invoice->relationLoaded('schoolClass') && $this->invoice->schoolClass ? [
                    'id' => $this->invoice->schoolClass->id,
                    'name' => $this->invoice->schoolClass->name,
                ] : null,
            ]),
            'amount' => (float) $this->amount,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'method' => $this->method,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'recorded_by' => $this->recorded_by,
            'recorder' => $this->whenLoaded('recorder', fn () => $this->recorder ? [
                'id' => $this->recorder->id,
                'name' => $this->recorder->name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

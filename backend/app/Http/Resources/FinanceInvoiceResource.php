<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'fee_type_id' => $this->fee_type_id,
            'fee_type' => $this->whenLoaded('feeType', fn () => [
                'id' => $this->feeType->id,
                'name' => $this->feeType->name,
                'code' => $this->feeType->code,
                'frequency' => $this->feeType->frequency,
            ]),
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'nis' => $this->student->nis,
                'class_id' => $this->student->class_id,
            ]),
            'class_id' => $this->class_id,
            'class' => $this->whenLoaded('schoolClass', fn () => $this->schoolClass ? [
                'id' => $this->schoolClass->id,
                'name' => $this->schoolClass->name,
                'code' => $this->schoolClass->code,
            ] : null),
            'academic_year_id' => $this->academic_year_id,
            'title' => $this->title,
            'period_label' => $this->period_label,
            'amount' => (float) $this->amount,
            'amount_paid' => (float) $this->amount_paid,
            'remaining' => (float) $this->remaining,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'status' => $this->status,
            'notes' => $this->notes,
            'batch_key' => $this->batch_key,
            'payments' => FinancePaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryLoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'copy_id' => $this->copy_id,
            'borrower_type' => $this->borrower_type,
            'borrower_id' => $this->borrower_id,
            'borrower_name' => $this->borrower_name,
            'borrower_identifier' => $this->borrower_identifier,
            'loan_date' => $this->loan_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'returned_at' => $this->returned_at?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'fine_amount' => (float) $this->fine_amount,
            'paid_amount' => $this->getPaidAmount(),
            'remaining_fine' => $this->getRemainingFine(),
            'notes' => $this->notes,
            'copy' => $this->when($this->relationLoaded('copy'), function () {
                $copy = $this->copy;
                return [
                    'id' => $copy->id ?? null,
                    'copy_code' => $copy->copy_code ?? null,
                    'book' => $copy->book ? [
                        'id' => $copy->book->id,
                        'title' => $copy->book->title,
                        'author' => $copy->book->author,
                        'isbn' => $copy->book->isbn,
                    ] : null,
                ];
            }),
            'fine_payments' => LibraryFinePaymentResource::collection($this->whenLoaded('finePayments')),
            'creator' => $this->when($this->relationLoaded('creator'), function () {
                return $this->creator ? ['id' => $this->creator->id, 'name' => $this->creator->name] : null;
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

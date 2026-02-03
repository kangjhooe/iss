<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryBookCopyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'book_id' => $this->book_id,
            'copy_code' => $this->copy_code,
            'status' => $this->status,
            'condition' => $this->condition,
            'notes' => $this->notes,
            'book' => $this->when($this->relationLoaded('book'), function () {
                return [
                    'id' => $this->book->id ?? null,
                    'title' => $this->book->title ?? null,
                    'author' => $this->book->author ?? null,
                    'isbn' => $this->book->isbn ?? null,
                    'shelf_code' => $this->book->shelf_code ?? null,
                ];
            }),
            'current_loan' => $this->when($this->relationLoaded('loans'), function () {
                $active = $this->loans->first(fn ($l) => in_array($l->status, ['Dipinjam', 'Terlambat'], true));
                return $active ? [
                    'id' => $active->id,
                    'borrower_name' => $active->borrower_name,
                    'due_date' => $active->due_date?->format('Y-m-d'),
                    'status' => $active->status,
                ] : null;
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

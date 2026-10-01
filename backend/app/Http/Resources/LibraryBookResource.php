<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryBookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'category_id' => $this->category_id,
            'isbn' => $this->isbn,
            'title' => $this->title,
            'author' => $this->author,
            'publisher' => $this->publisher,
            'year' => $this->year,
            'language' => $this->language,
            'pages' => $this->pages,
            'shelf_code' => $this->shelf_code,
            'grade' => $this->grade,
            'acquired_at' => $this->acquired_at?->format('Y-m-d'),
            'description' => $this->description,
            'cover_url' => $this->cover_path ? asset('storage/' . $this->cover_path) : null,
            'has_ebook' => $this->hasEbook(),
            'is_public_ebook' => (bool) $this->is_public_ebook && $this->hasEbook(),
            'ebook_view_count' => (int) ($this->ebook_view_count ?? 0),
            'available_copies_count' => $this->when(isset($this->available_copies_count), fn () => $this->available_copies_count, $this->getAvailableCopiesCount()),
            'copies_count' => $this->when(
                isset($this->copies_count) || $this->relationLoaded('copies'),
                fn () => (int) ($this->copies_count ?? $this->copies->count())
            ),
            'category' => $this->when($this->relationLoaded('category'), function () {
                return [
                    'id' => $this->category->id ?? null,
                    'code' => $this->category->code ?? null,
                    'name' => $this->category->name ?? null,
                ];
            }),
            'creator' => $this->when($this->relationLoaded('creator'), function () {
                return $this->creator ? ['id' => $this->creator->id, 'name' => $this->creator->name] : null;
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

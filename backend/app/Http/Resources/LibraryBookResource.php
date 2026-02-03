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
            'description' => $this->description,
            'cover_url' => $this->cover_path ? asset('storage/' . $this->cover_path) : null,
            'available_copies_count' => $this->when(isset($this->available_copies_count), fn () => $this->available_copies_count, $this->getAvailableCopiesCount()),
            'copies_count' => $this->whenLoaded('copies', fn () => $this->copies->count()),
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

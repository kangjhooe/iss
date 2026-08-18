<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'type' => $this->type,
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'cover_path' => $this->cover_path,
            'cover_url' => $this->cover_url,
            'published_at' => $this->published_at?->toIso8601String(),
            'is_published' => (bool) $this->is_published,
            'sort' => (int) $this->sort,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(fn ($img) => [
                    'id' => $img->id,
                    'path' => $img->path,
                    'url' => $img->url,
                    'caption' => $img->caption,
                    'sort' => (int) $img->sort,
                ]);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Services;

use App\Models\SchoolPost;
use App\Models\SchoolPostImage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SchoolPostService
{
    public function list(array $filters, int $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        $query = SchoolPost::forInstitution($institutionId)
            ->with(['images', 'creator:id,name'])
            ->orderByDesc('sort')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['is_published']) && $filters['is_published'] !== '' && $filters['is_published'] !== null) {
            $query->where('is_published', filter_var($filters['is_published'], FILTER_VALIDATE_BOOLEAN));
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('body', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate($perPage);
    }

    public function create(array $data, int $institutionId, int $userId, ?UploadedFile $cover = null, array $galleryFiles = []): SchoolPost
    {
        return DB::transaction(function () use ($data, $institutionId, $userId, $cover, $galleryFiles) {
            $data['institution_id'] = $institutionId;
            $data['created_by'] = $userId;
            $data['slug'] = $this->uniqueSlug($institutionId, $data['title'], $data['slug'] ?? null);
            $data['is_published'] = (bool) ($data['is_published'] ?? false);
            $data['sort'] = (int) ($data['sort'] ?? 0);
            if ($data['is_published'] && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            if ($cover) {
                $data['cover_path'] = $cover->store("school-posts/{$institutionId}", 'public');
            }

            $post = SchoolPost::create($data);
            $this->storeGalleryImages($post, $galleryFiles);

            return $post->load(['images', 'creator:id,name']);
        });
    }

    public function update(SchoolPost $post, array $data, ?UploadedFile $cover = null, array $galleryFiles = []): SchoolPost
    {
        return DB::transaction(function () use ($post, $data, $cover, $galleryFiles) {
            if (isset($data['title']) && empty($data['slug'])) {
                $data['slug'] = $this->uniqueSlug((int) $post->institution_id, $data['title'], null, $post->id);
            } elseif (!empty($data['slug'])) {
                $data['slug'] = $this->uniqueSlug((int) $post->institution_id, $data['title'] ?? $post->title, $data['slug'], $post->id);
            }

            if (array_key_exists('is_published', $data)) {
                $data['is_published'] = (bool) $data['is_published'];
                if ($data['is_published'] && empty($data['published_at']) && !$post->published_at) {
                    $data['published_at'] = now();
                }
            }

            if ($cover) {
                if ($post->cover_path) {
                    Storage::disk('public')->delete($post->cover_path);
                }
                $data['cover_path'] = $cover->store("school-posts/{$post->institution_id}", 'public');
            }

            $post->update($data);
            $this->storeGalleryImages($post, $galleryFiles);

            return $post->fresh(['images', 'creator:id,name']);
        });
    }

    public function delete(SchoolPost $post): void
    {
        DB::transaction(function () use ($post) {
            foreach ($post->images as $image) {
                if ($image->path) {
                    Storage::disk('public')->delete($image->path);
                }
                $image->delete();
            }
            if ($post->cover_path) {
                Storage::disk('public')->delete($post->cover_path);
            }
            $post->delete();
        });
    }

    public function listPublishedByInstitution(int $institutionId, ?string $type = null, int $limit = 20)
    {
        $query = SchoolPost::forInstitution($institutionId)
            ->published()
            ->with(['images'])
            ->orderByDesc('sort')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($type) {
            $query->where('type', $type);
        }

        return $query->limit($limit)->get();
    }

    protected function storeGalleryImages(SchoolPost $post, array $files): void
    {
        if (empty($files)) {
            return;
        }

        $maxSort = (int) $post->images()->max('sort');
        foreach ($files as $i => $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }
            $path = $file->store("school-posts/{$post->institution_id}/gallery", 'public');
            SchoolPostImage::create([
                'school_post_id' => $post->id,
                'path' => $path,
                'caption' => null,
                'sort' => $maxSort + $i + 1,
            ]);
        }
    }

    protected function uniqueSlug(int $institutionId, string $title, ?string $slug = null, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $title) ?: 'post';
        $candidate = $base;
        $i = 1;
        while (
            SchoolPost::withTrashed()
                ->where('institution_id', $institutionId)
                ->where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }
}

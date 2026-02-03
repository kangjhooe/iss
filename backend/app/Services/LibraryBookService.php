<?php

namespace App\Services;

use App\Models\LibraryBook;
use App\Models\LibraryBookCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LibraryBookService
{
    public function listCategories(array $filters, ?int $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        $query = LibraryBookCategory::query()
            ->forInstitution($institutionId)
            ->withCount('books');

        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(function (Builder $b) use ($q) {
                $b->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            });
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('code')->paginate($perPage);
    }

    public function listBooks(array $filters, ?int $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        $query = LibraryBook::query()
            ->forInstitution($institutionId)
            ->with(['category'])
            ->withCount('copies');

        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(function (Builder $b) use ($q) {
                $b->where('title', 'like', "%{$q}%")
                    ->orWhere('author', 'like', "%{$q}%")
                    ->orWhere('isbn', 'like', "%{$q}%");
            });
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->orderBy('title')->paginate($perPage);
    }

    public function createCategory(array $data, int $institutionId): LibraryBookCategory
    {
        $data['institution_id'] = $institutionId;
        $data['is_active'] = $data['is_active'] ?? true;
        return LibraryBookCategory::create($data);
    }

    public function updateCategory(LibraryBookCategory $category, array $data): LibraryBookCategory
    {
        $category->update($data);
        return $category->fresh();
    }

    public function createBook(array $data, int $institutionId, ?int $userId, $coverFile = null): LibraryBook
    {
        return DB::transaction(function () use ($data, $institutionId, $userId, $coverFile) {
            $data['institution_id'] = $institutionId;
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;

            if ($coverFile) {
                $data['cover_path'] = $this->storeCover($coverFile, $institutionId);
            }

            return LibraryBook::create($data);
        });
    }

    public function updateBook(LibraryBook $book, array $data, ?int $userId, $coverFile = null): LibraryBook
    {
        return DB::transaction(function () use ($book, $data, $userId, $coverFile) {
            $data['updated_by'] = $userId;

            if ($coverFile) {
                if ($book->cover_path && Storage::disk('public')->exists($book->cover_path)) {
                    Storage::disk('public')->delete($book->cover_path);
                }
                $data['cover_path'] = $this->storeCover($coverFile, $book->institution_id);
            }

            $book->update($data);
            return $book->fresh(['category', 'creator', 'updater']);
        });
    }

    private function storeCover($file, int $institutionId): string
    {
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName = time() . '_' . $name . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('library/' . $institutionId, $fileName, 'public');
    }
}

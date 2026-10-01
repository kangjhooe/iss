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
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
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
        if (!empty($filters['has_ebook'])) {
            $query->whereNotNull('ebook_path');
        }

        $sortBy = $filters['sort_by'] ?? 'title';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSort = ['title', 'author', 'isbn', 'year', 'copies_count', 'category', 'ebook'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'title';
        }

        if ($sortBy === 'copies_count') {
            $query->orderBy('copies_count', $sortDir);
        } elseif ($sortBy === 'category') {
            $query->orderBy(
                LibraryBookCategory::query()
                    ->select('name')
                    ->whereColumn('library_book_categories.id', 'library_books.category_id')
                    ->limit(1),
                $sortDir
            );
        } elseif ($sortBy === 'ebook') {
            $query->orderByRaw('CASE WHEN library_books.ebook_path IS NULL THEN 0 ELSE 1 END ' . $sortDir);
        } else {
            $query->orderBy('library_books.' . $sortBy, $sortDir);
        }

        if ($sortBy !== 'title') {
            $query->orderBy('library_books.title', 'asc');
        }

        return $query->paginate($perPage);
    }

    public function listEbooks(array $filters, ?int $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        $filters['has_ebook'] = true;
        return $this->listBooks($filters, $institutionId, $perPage);
    }

    /**
     * Katalog ebook publik (tanpa login) — hanya buku dengan PDF + is_public_ebook.
     */
    public function listPublicEbooks(array $filters, int $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        $filters['has_ebook'] = true;
        $query = LibraryBook::query()
            ->forInstitution($institutionId)
            ->whereNotNull('ebook_path')
            ->where('is_public_ebook', true)
            ->with(['category']);

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

    public function createBook(array $data, int $institutionId, ?int $userId, $coverFile = null, $ebookFile = null): LibraryBook
    {
        return DB::transaction(function () use ($data, $institutionId, $userId, $coverFile, $ebookFile) {
            $copiesCount = (int) ($data['copies_count'] ?? 0);
            unset($data['cover'], $data['ebook'], $data['remove_ebook'], $data['copies_count']);
            $data['institution_id'] = $institutionId;
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;

            if ($coverFile) {
                $data['cover_path'] = $this->storeCover($coverFile, $institutionId);
            }
            if ($ebookFile) {
                $data['ebook_path'] = $this->storeEbook($ebookFile, $institutionId);
            }

            // Publik hanya bermakna jika ada ebook
            if (empty($data['ebook_path'])) {
                $data['is_public_ebook'] = false;
            } else {
                $data['is_public_ebook'] = !empty($data['is_public_ebook']);
            }

            if (empty($data['acquired_at'])) {
                $data['acquired_at'] = now()->toDateString();
            }

            $book = LibraryBook::create($data);

            if ($copiesCount > 0) {
                app(LibraryInventoryNumberService::class)->createCopies($book, min($copiesCount, 100));
            }

            return $book;
        });
    }

    public function updateBook(LibraryBook $book, array $data, ?int $userId, $coverFile = null, $ebookFile = null): LibraryBook
    {
        return DB::transaction(function () use ($book, $data, $userId, $coverFile, $ebookFile) {
            $removeEbook = !empty($data['remove_ebook']);
            unset($data['cover'], $data['ebook'], $data['remove_ebook']);
            $data['updated_by'] = $userId;

            if ($coverFile) {
                if ($book->cover_path && Storage::disk('public')->exists($book->cover_path)) {
                    Storage::disk('public')->delete($book->cover_path);
                }
                $data['cover_path'] = $this->storeCover($coverFile, $book->institution_id);
            }

            if ($ebookFile) {
                $this->deleteEbookFile($book);
                $data['ebook_path'] = $this->storeEbook($ebookFile, $book->institution_id);
            } elseif ($removeEbook) {
                $this->deleteEbookFile($book);
                $data['ebook_path'] = null;
                $data['is_public_ebook'] = false;
            }

            $willHaveEbook = array_key_exists('ebook_path', $data)
                ? !empty($data['ebook_path'])
                : $book->hasEbook();

            if (!$willHaveEbook) {
                $data['is_public_ebook'] = false;
            } elseif (array_key_exists('is_public_ebook', $data)) {
                $data['is_public_ebook'] = (bool) $data['is_public_ebook'];
            }

            $book->update($data);
            return $book->fresh(['category', 'creator', 'updater']);
        });
    }

    public function deleteEbookFile(LibraryBook $book): void
    {
        if ($book->ebook_path && Storage::disk('local')->exists($book->ebook_path)) {
            Storage::disk('local')->delete($book->ebook_path);
        }
    }

    private function storeCover($file, int $institutionId): string
    {
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName = time() . '_' . $name . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('library/' . $institutionId, $fileName, 'public');
    }

    private function storeEbook($file, int $institutionId): string
    {
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName = time() . '_' . $name . '.pdf';
        return $file->storeAs('library/ebooks/' . $institutionId, $fileName, 'local');
    }
}

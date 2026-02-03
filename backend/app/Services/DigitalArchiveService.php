<?php

namespace App\Services;

use App\Models\DigitalArchive;
use App\Models\DigitalArchiveCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DigitalArchiveService
{
    public function list(array $filters, ?int $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        $query = DigitalArchive::query()
            ->with(['category', 'creator'])
            ->when($institutionId !== null, fn (Builder $q) => $q->where('institution_id', $institutionId));

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('file_name', 'like', $term)
                    ->orWhere('reference_number', 'like', $term);
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('digital_archive_category_id', $filters['category_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('document_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('document_date', '<=', $filters['date_to']);
        }

        $query->orderByDesc('document_date')->orderByDesc('created_at');

        return $query->paginate($perPage);
    }

    public function create(array $data, int $institutionId, int $userId, ?\Illuminate\Http\UploadedFile $file = null): DigitalArchive
    {
        return DB::transaction(function () use ($data, $institutionId, $userId, $file) {
            $data['institution_id'] = $institutionId;
            $data['created_by'] = $userId;

            if ($file) {
                $path = $this->storeFile($file, $institutionId);
                $data['file_path'] = $path;
                $data['file_name'] = $file->getClientOriginalName();
                $data['file_size'] = $file->getSize();
                $data['mime_type'] = $file->getMimeType();
            }

            return DigitalArchive::create($data);
        });
    }

    public function update(DigitalArchive $archive, array $data, ?\Illuminate\Http\UploadedFile $file = null): DigitalArchive
    {
        return DB::transaction(function () use ($archive, $data, $file) {
            if ($file) {
                if ($archive->file_path && Storage::disk('public')->exists($archive->file_path)) {
                    Storage::disk('public')->delete($archive->file_path);
                }
                $path = $this->storeFile($file, $archive->institution_id);
                $data['file_path'] = $path;
                $data['file_name'] = $file->getClientOriginalName();
                $data['file_size'] = $file->getSize();
                $data['mime_type'] = $file->getMimeType();
            }

            $archive->update($data);
            return $archive->fresh(['category', 'creator']);
        });
    }

    public function delete(DigitalArchive $archive): void
    {
        DB::transaction(function () use ($archive) {
            $archive->delete();
            // Optionally delete file on soft delete; here we keep file for restore
        });
    }

    protected function storeFile(\Illuminate\Http\UploadedFile $file, int $institutionId): string
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $fileName = time() . '_' . $safeName . '.' . $extension;
        return $file->storeAs(
            'digital-archives/' . $institutionId,
            $fileName,
            'public'
        );
    }

    public function listCategories(int $institutionId): \Illuminate\Database\Eloquent\Collection
    {
        return DigitalArchiveCategory::query()
            ->where('institution_id', $institutionId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function createCategory(array $data, int $institutionId): DigitalArchiveCategory
    {
        $data['institution_id'] = $institutionId;
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }
        return DigitalArchiveCategory::create($data);
    }
}

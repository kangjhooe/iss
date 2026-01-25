<?php

namespace App\Repositories;

use App\Models\InventoryCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryCategoryRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return InventoryCategory::class;
    }

    /**
     * Get list of categories with filters.
     */
    public function list(array $filters, ?int $institutionId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        // Filter by institution if provided
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $perPage = min($perPage, 100);

        return $query->with(['institution'])
            ->orderBy('code', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get category with relationships.
     */
    public function findWithRelations(int $id): InventoryCategory
    {
        return $this->query()
            ->with(['institution', 'items'])
            ->findOrFail($id);
    }
}

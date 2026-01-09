<?php

namespace App\Repositories;

use App\Models\Institution;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InstitutionRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return Institution::class;
    }

    /**
     * Get list of institutions with filters.
     */
    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('npsn', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = min($perPage, 100);

        return $query->select(['id', 'name', 'npsn', 'level', 'type', 'is_active', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get institution with relationships.
     */
    public function findWithRelations(int $id): Institution
    {
        return $this->query()
            ->with(['users', 'students', 'teachers'])
            ->findOrFail($id);
    }
}

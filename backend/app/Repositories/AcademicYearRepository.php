<?php

namespace App\Repositories;

use App\Models\AcademicYear;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AcademicYearRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return AcademicYear::class;
    }

    /**
     * Get list of academic years with filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = min($perPage, 100);

        return $query->with('semesters')
            ->orderBy('start_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get academic year with relationships.
     */
    public function findWithRelations(int $id): AcademicYear
    {
        return $this->query()
            ->with(['semesters'])
            ->findOrFail($id);
    }

    /**
     * Check if there's already an active academic year.
     */
    public function hasActiveAcademicYear(?int $excludeId = null): bool
    {
        $query = $this->query()
            ->where('status', 'Aktif');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get active academic year.
     */
    public function getActive(): ?AcademicYear
    {
        return $this->query()
            ->where('status', 'Aktif')
            ->first();
    }

    /**
     * Get current academic year (based on date).
     */
    public function getCurrent(): ?AcademicYear
    {
        $now = now();
        return $this->query()
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where('status', 'Aktif')
            ->first();
    }
}

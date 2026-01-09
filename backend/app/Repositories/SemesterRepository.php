<?php

namespace App\Repositories;

use App\Models\Semester;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SemesterRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return Semester::class;
    }

    /**
     * Get list of semesters with filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        // Apply filters
        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (isset($filters['name'])) {
            $query->where('name', $filters['name']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = min($perPage, 100);

        return $query->with('academicYear')
            ->orderBy('academic_year_id', 'desc')
            ->orderBy('order', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get semester with relationships.
     */
    public function findWithRelations(int $id): Semester
    {
        return $this->query()
            ->with(['academicYear'])
            ->findOrFail($id);
    }

    /**
     * Get semesters for a specific academic year.
     */
    public function getByAcademicYear(int $academicYearId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->query()
            ->where('academic_year_id', $academicYearId)
            ->orderBy('order', 'asc')
            ->get();
    }

    /**
     * Get active semester.
     */
    public function getActive(): ?Semester
    {
        return $this->query()
            ->where('status', 'Aktif')
            ->first();
    }

    /**
     * Get active semester for a specific academic year.
     */
    public function getActiveForAcademicYear(int $academicYearId): ?Semester
    {
        return $this->query()
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'Aktif')
            ->first();
    }

    /**
     * Check if there's already an active semester for an academic year.
     */
    public function hasActiveSemester(int $academicYearId, ?int $excludeId = null): bool
    {
        $query = $this->query()
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'Aktif');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}

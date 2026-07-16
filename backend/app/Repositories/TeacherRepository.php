<?php

namespace App\Repositories;

use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TeacherRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return Teacher::class;
    }

    /**
     * Get list of teachers with filters.
     */
    public function list(array $filters, ?int $institutionId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        // Filter by institution if provided
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('nip', 'like', '%' . $search . '%')
                  ->orWhere('nuptk', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
        }

        if (isset($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        $perPage = min($perPage, 100);

        return $query->select(['id', 'institution_id', 'nip', 'nuptk', 'name', 'gender', 'status', 'employment_status', 'notes', 'created_at'])
            ->with('institution:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get teacher with relationships.
     */
    public function findWithRelations(int $id): Teacher
    {
        return $this->query()
            ->with('institution')
            ->findOrFail($id);
    }
}

<?php

namespace App\Services;

use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class InstitutionService
{
    /**
     * Get list of institutions with filters.
     */
    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Institution::query();

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

        $perPage = min($perPage, 100); // Max 100 per page

        return $query->select(['id', 'name', 'npsn', 'level', 'type', 'is_active', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new institution.
     */
    public function create(array $data): Institution
    {
        $institution = Institution::create($data);

        Log::info('Institution created', [
            'institution_id' => $institution->id,
        ]);

        return $institution;
    }

    /**
     * Get institution by ID.
     */
    public function find(int $id): Institution
    {
        return Institution::with(['users', 'students', 'teachers'])->findOrFail($id);
    }

    /**
     * Get user's institution.
     */
    public function getUserInstitution(int $userId): ?Institution
    {
        $user = \App\Models\User::with('institution')->findOrFail($userId);
        return $user->institution;
    }

    /**
     * Update institution.
     */
    public function update(Institution $institution, array $data): Institution
    {
        $institution->update($data);

        Log::info('Institution updated', [
            'institution_id' => $institution->id,
        ]);

        return $institution->fresh();
    }

    /**
     * Delete institution (soft delete).
     */
    public function delete(Institution $institution): bool
    {
        $institutionId = $institution->id;
        $result = $institution->delete();

        Log::info('Institution deleted', [
            'institution_id' => $institutionId,
        ]);

        return $result;
    }
}

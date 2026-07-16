<?php

namespace App\Services;

use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class TeacherService
{
    /**
     * Get list of teachers with filters.
     */
    public function list(array $filters, ?int $institutionId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Teacher::query();

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

        $perPage = min($perPage, 100); // Max 100 per page

        return $query->select(['id', 'institution_id', 'nip', 'nuptk', 'name', 'gender', 'status', 'employment_status', 'notes', 'created_at'])
            ->with('institution:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new teacher.
     */
    public function create(array $data): Teacher
    {
        $teacher = Teacher::create($data);

        Log::info('Teacher created', [
            'teacher_id' => $teacher->id,
            'institution_id' => $teacher->institution_id,
        ]);

        return $teacher;
    }

    /**
     * Get teacher by ID.
     */
    public function find(int $id): Teacher
    {
        return Teacher::with('institution')->findOrFail($id);
    }

    /**
     * Update teacher.
     */
    public function update(Teacher $teacher, array $data): Teacher
    {
        $teacher->update($data);

        Log::info('Teacher updated', [
            'teacher_id' => $teacher->id,
        ]);

        return $teacher->fresh(['institution']);
    }

    /**
     * Delete teacher (soft delete).
     */
    public function delete(Teacher $teacher): bool
    {
        $teacherId = $teacher->id;
        $result = $teacher->delete();

        Log::info('Teacher deleted', [
            'teacher_id' => $teacherId,
        ]);

        return $result;
    }
}

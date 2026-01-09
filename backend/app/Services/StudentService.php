<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class StudentService
{
    /**
     * Get list of students with filters.
     */
    public function list(array $filters, ?int $institutionId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Student::query();

        // Filter by institution if provided
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('nis', 'like', '%' . $search . '%')
                  ->orWhere('nisn', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['class'])) {
            $query->where('class', $filters['class']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        $perPage = min($perPage, 100); // Max 100 per page

        return $query->select(['id', 'institution_id', 'nis', 'nisn', 'name', 'gender', 'class', 'status', 'created_at'])
            ->with('institution:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new student.
     */
    public function create(array $data): Student
    {
        $student = Student::create($data);

        Log::info('Student created', [
            'student_id' => $student->id,
            'institution_id' => $student->institution_id,
        ]);

        return $student;
    }

    /**
     * Get student by ID.
     */
    public function find(int $id): Student
    {
        return Student::with('institution')->findOrFail($id);
    }

    /**
     * Update student.
     */
    public function update(Student $student, array $data): Student
    {
        $student->update($data);

        Log::info('Student updated', [
            'student_id' => $student->id,
        ]);

        return $student->fresh(['institution']);
    }

    /**
     * Delete student (soft delete).
     */
    public function delete(Student $student): bool
    {
        $studentId = $student->id;
        $result = $student->delete();

        Log::info('Student deleted', [
            'student_id' => $studentId,
        ]);

        return $result;
    }
}

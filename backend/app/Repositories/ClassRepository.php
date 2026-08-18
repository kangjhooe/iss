<?php

namespace App\Repositories;

use App\Models\SchoolClass;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClassRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return SchoolClass::class;
    }

    /**
     * Get list of classes with filters.
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
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['grade'])) {
            $query->where('grade', $filters['grade']);
        }

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (isset($filters['semester_id'])) {
            $query->where('semester_id', $filters['semester_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        if (isset($filters['program_keahlian_id'])) {
            $query->where('program_keahlian_id', $filters['program_keahlian_id']);
        }

        if (!empty($filters['program_keahlian_ids']) && is_array($filters['program_keahlian_ids'])) {
            $query->whereIn('program_keahlian_id', $filters['program_keahlian_ids']);
        }

        $perPage = min($perPage, 100);

        return $query->withCount('students')
            ->with([
                'institution:id,name,npsn',
                'room:id,name,code',
                'teacher:id,name,nip,nuptk',
                'academicYear:id,code,name',
                'semester:id,name',
                'programKeahlian:id,code,name',
            ])
            ->orderBy('grade', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get class with relationships.
     */
    public function findWithRelations(int $id): SchoolClass
    {
        return $this->query()
            ->with(['institution', 'room', 'teacher', 'students', 'academicYear', 'semester', 'programKeahlian'])
            ->findOrFail($id);
    }

    /**
     * Check if teacher is already a wali kelas for another active class in the same academic year.
     */
    public function isTeacherAlreadyWaliKelas(int $teacherId, int $academicYearId, ?int $excludeClassId = null): bool
    {
        $query = $this->query()
            ->where('teacher_id', $teacherId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'Aktif');

        if ($excludeClassId) {
            $query->where('id', '!=', $excludeClassId);
        }

        return $query->exists();
    }

    /**
     * Check if room is already used by another class in the same academic year.
     */
    public function isRoomAlreadyUsed(int $roomId, int $academicYearId, ?int $excludeClassId = null): bool
    {
        $query = $this->query()
            ->where('room_id', $roomId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'Aktif');

        if ($excludeClassId) {
            $query->where('id', '!=', $excludeClassId);
        }

        return $query->exists();
    }
}

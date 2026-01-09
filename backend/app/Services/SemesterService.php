<?php

namespace App\Services;

use App\Models\Semester;
use App\Repositories\SemesterRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SemesterService
{
    public function __construct(
        protected SemesterRepository $semesterRepository
    ) {}

    /**
     * Get list of semesters with filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->semesterRepository->list($filters, $perPage);
    }

    /**
     * Create a new semester.
     */
    public function create(array $data): Semester
    {
        // Validate date range
        if ($data['start_date'] >= $data['end_date']) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal akhir harus setelah tanggal mulai.'
            ]);
        }

        // Validate name (must be Ganjil or Genap)
        if (!in_array($data['name'], ['Ganjil', 'Genap'])) {
            throw ValidationException::withMessages([
                'name' => 'Nama semester harus Ganjil atau Genap.'
            ]);
        }

        // Set order based on name
        if (!isset($data['order'])) {
            $data['order'] = $data['name'] === 'Ganjil' ? 1 : 2;
        }

        // Check if semester with same name already exists for this academic year
        if ($this->semesterRepository->query()
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('name', $data['name'])
            ->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Semester ' . $data['name'] . ' sudah ada untuk tahun ajaran ini.'
            ]);
        }

        // Validate dates are within academic year dates
        $academicYear = \App\Models\AcademicYear::findOrFail($data['academic_year_id']);
        if ($data['start_date'] < $academicYear->start_date || $data['end_date'] > $academicYear->end_date) {
            throw ValidationException::withMessages([
                'start_date' => 'Tanggal semester harus berada dalam rentang tahun ajaran.',
                'end_date' => 'Tanggal semester harus berada dalam rentang tahun ajaran.',
            ]);
        }

        // If setting as active, deactivate other active semesters for this academic year
        if (isset($data['status']) && $data['status'] === 'Aktif') {
            if ($this->semesterRepository->hasActiveSemester($data['academic_year_id'])) {
                throw ValidationException::withMessages([
                    'status' => 'Sudah ada semester aktif untuk tahun ajaran ini. Nonaktifkan semester aktif terlebih dahulu.'
                ]);
            }
        }

        $semester = $this->semesterRepository->create($data);

        Log::info('Semester created', [
            'semester_id' => $semester->id,
            'academic_year_id' => $semester->academic_year_id,
            'name' => $semester->name,
        ]);

        return $semester;
    }

    /**
     * Get semester by ID.
     */
    public function find(int $id): Semester
    {
        return $this->semesterRepository->findWithRelations($id);
    }

    /**
     * Update semester.
     */
    public function update(Semester $semester, array $data): Semester
    {
        // Validate date range
        if (isset($data['start_date']) && isset($data['end_date'])) {
            if ($data['start_date'] >= $data['end_date']) {
                throw ValidationException::withMessages([
                    'end_date' => 'Tanggal akhir harus setelah tanggal mulai.'
                ]);
            }
        }

        // Validate name if provided
        if (isset($data['name'])) {
            if (!in_array($data['name'], ['Ganjil', 'Genap'])) {
                throw ValidationException::withMessages([
                    'name' => 'Nama semester harus Ganjil atau Genap.'
                ]);
            }

            // Check if semester with same name already exists for this academic year (exclude current)
            if ($this->semesterRepository->query()
                ->where('academic_year_id', $semester->academic_year_id)
                ->where('name', $data['name'])
                ->where('id', '!=', $semester->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'name' => 'Semester ' . $data['name'] . ' sudah ada untuk tahun ajaran ini.'
                ]);
            }
        }

        // Validate dates are within academic year dates
        $academicYear = $semester->academicYear;
        $startDate = $data['start_date'] ?? $semester->start_date;
        $endDate = $data['end_date'] ?? $semester->end_date;
        
        if ($startDate < $academicYear->start_date || $endDate > $academicYear->end_date) {
            throw ValidationException::withMessages([
                'start_date' => 'Tanggal semester harus berada dalam rentang tahun ajaran.',
                'end_date' => 'Tanggal semester harus berada dalam rentang tahun ajaran.',
            ]);
        }

        // If setting as active, deactivate other active semesters for this academic year
        if (isset($data['status']) && $data['status'] === 'Aktif') {
            if ($this->semesterRepository->hasActiveSemester($semester->academic_year_id, $semester->id)) {
                throw ValidationException::withMessages([
                    'status' => 'Sudah ada semester aktif untuk tahun ajaran ini. Nonaktifkan semester aktif terlebih dahulu.'
                ]);
            }
        }

        $this->semesterRepository->update($semester, $data);

        Log::info('Semester updated', [
            'semester_id' => $semester->id,
        ]);

        return $semester->fresh(['academicYear']);
    }

    /**
     * Delete semester (soft delete).
     */
    public function delete(Semester $semester): bool
    {
        $semesterId = $semester->id;
        $result = $this->semesterRepository->delete($semester);

        Log::info('Semester deleted', [
            'semester_id' => $semesterId,
        ]);

        return $result;
    }

    /**
     * Get semesters for a specific academic year.
     */
    public function getByAcademicYear(int $academicYearId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->semesterRepository->getByAcademicYear($academicYearId);
    }

    /**
     * Get active semester.
     */
    public function getActive(): ?Semester
    {
        return $this->semesterRepository->getActive();
    }

    /**
     * Get active semester for a specific academic year.
     */
    public function getActiveForAcademicYear(int $academicYearId): ?Semester
    {
        return $this->semesterRepository->getActiveForAcademicYear($academicYearId);
    }

    /**
     * Activate a semester (deactivate others for the same academic year).
     */
    public function activate(Semester $semester): Semester
    {
        // Deactivate all other semesters for the same academic year
        $this->semesterRepository->query()
            ->where('academic_year_id', $semester->academic_year_id)
            ->where('id', '!=', $semester->id)
            ->where('status', 'Aktif')
            ->update(['status' => 'Selesai']);

        $semester->update(['status' => 'Aktif']);

        Log::info('Semester activated', [
            'semester_id' => $semester->id,
        ]);

        return $semester->fresh();
    }
}

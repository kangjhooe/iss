<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Repositories\AcademicYearRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AcademicYearService
{
    public function __construct(
        protected AcademicYearRepository $academicYearRepository
    ) {}

    /**
     * Get list of academic years with filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->academicYearRepository->list($filters, $perPage);
    }

    /**
     * Create a new academic year.
     */
    public function create(array $data): AcademicYear
    {
        // Validate date range
        if ($data['start_date'] >= $data['end_date']) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal akhir harus setelah tanggal mulai.'
            ]);
        }

        // Validate code format (should be like 2025/2026)
        if (!preg_match('/^\d{4}\/\d{4}$/', $data['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Format tahun ajaran tidak valid. Gunakan format: YYYY/YYYY (contoh: 2025/2026).'
            ]);
        }

        // Check if code already exists
        if ($this->academicYearRepository->query()->where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages([
                'code' => 'Tahun ajaran dengan kode ini sudah ada.'
            ]);
        }

        // If setting as active, deactivate other active academic years
        if (isset($data['status']) && $data['status'] === 'Aktif') {
            if ($this->academicYearRepository->hasActiveAcademicYear()) {
                // Optionally deactivate others, or throw error
                throw ValidationException::withMessages([
                    'status' => 'Sudah ada tahun ajaran aktif. Nonaktifkan tahun ajaran aktif terlebih dahulu.'
                ]);
            }
        }

        $academicYear = $this->academicYearRepository->create($data);

        Log::info('Academic year created', [
            'academic_year_id' => $academicYear->id,
            'code' => $academicYear->code,
        ]);

        return $academicYear;
    }

    /**
     * Get academic year by ID.
     */
    public function find(int $id): AcademicYear
    {
        return $this->academicYearRepository->findWithRelations($id);
    }

    /**
     * Update academic year.
     */
    public function update(AcademicYear $academicYear, array $data): AcademicYear
    {
        // Validate date range
        if (isset($data['start_date']) && isset($data['end_date'])) {
            if ($data['start_date'] >= $data['end_date']) {
                throw ValidationException::withMessages([
                    'end_date' => 'Tanggal akhir harus setelah tanggal mulai.'
                ]);
            }
        }

        // Validate code format if provided
        if (isset($data['code'])) {
            if (!preg_match('/^\d{4}\/\d{4}$/', $data['code'])) {
                throw ValidationException::withMessages([
                    'code' => 'Format tahun ajaran tidak valid. Gunakan format: YYYY/YYYY (contoh: 2025/2026).'
                ]);
            }

            // Check if code already exists (exclude current)
            if ($this->academicYearRepository->query()
                ->where('code', $data['code'])
                ->where('id', '!=', $academicYear->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'code' => 'Tahun ajaran dengan kode ini sudah ada.'
                ]);
            }
        }

        // If setting as active, deactivate other active academic years
        if (isset($data['status']) && $data['status'] === 'Aktif') {
            if ($this->academicYearRepository->hasActiveAcademicYear($academicYear->id)) {
                throw ValidationException::withMessages([
                    'status' => 'Sudah ada tahun ajaran aktif. Nonaktifkan tahun ajaran aktif terlebih dahulu.'
                ]);
            }
        }

        $this->academicYearRepository->update($academicYear, $data);

        Log::info('Academic year updated', [
            'academic_year_id' => $academicYear->id,
        ]);

        return $academicYear->fresh(['semesters']);
    }

    /**
     * Delete academic year (soft delete).
     */
    public function delete(AcademicYear $academicYear): bool
    {
        // Prevent deletion if there are related records
        if ($academicYear->classes()->count() > 0) {
            throw ValidationException::withMessages([
                'academic_year' => 'Tidak dapat menghapus tahun ajaran yang sudah memiliki kelas.'
            ]);
        }

        $academicYearId = $academicYear->id;
        $result = $this->academicYearRepository->delete($academicYear);

        Log::info('Academic year deleted', [
            'academic_year_id' => $academicYearId,
        ]);

        return $result;
    }

    /**
     * Get active academic year.
     */
    public function getActive(): ?AcademicYear
    {
        return $this->academicYearRepository->getActive();
    }

    /**
     * Get current academic year (based on date).
     */
    public function getCurrent(): ?AcademicYear
    {
        return $this->academicYearRepository->getCurrent();
    }

    /**
     * Activate an academic year (deactivate others).
     */
    public function activate(AcademicYear $academicYear): AcademicYear
    {
        // Deactivate all other academic years
        $this->academicYearRepository->query()
            ->where('id', '!=', $academicYear->id)
            ->where('status', 'Aktif')
            ->update(['status' => 'Arsip']);

        $academicYear->update(['status' => 'Aktif']);

        Log::info('Academic year activated', [
            'academic_year_id' => $academicYear->id,
        ]);

        return $academicYear->fresh();
    }
}

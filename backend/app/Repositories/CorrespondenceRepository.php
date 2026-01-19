<?php

namespace App\Repositories;

use App\Models\Correspondence;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CorrespondenceRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return Correspondence::class;
    }

    /**
     * Get list of correspondence with filters.
     */
    public function list(array $filters, ?int $institutionId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        // Filter by institution if provided
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        // Apply filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['letter_type_code'])) {
            $query->where('letter_type_code', $filters['letter_type_code']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', '%' . $search . '%')
                  ->orWhere('letter_number', 'like', '%' . $search . '%')
                  ->orWhere('reference_number', 'like', '%' . $search . '%')
                  ->orWhere('from', 'like', '%' . $search . '%')
                  ->orWhere('to', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        $perPage = min($perPage, 100);

        // Ensure we're not including soft deleted records unless needed
        // Load relationships - withDefault is handled in model relationships
        try {
            return $query->with(['category', 'creator', 'approver', 'institution'])
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        } catch (\Exception $e) {
            \Log::error('Error in correspondence repository list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $filters,
                'institution_id' => $institutionId,
            ]);
            throw $e;
        }
    }

    /**
     * Get correspondence with relationships.
     */
    public function findWithRelations(int $id): Correspondence
    {
        return $this->query()
            ->with([
                'institution',
                'category',
                'creator',
                'approver',
                'dispositions.fromUser',
                'dispositions.toUser',
                'attachments',
                'histories.user'
            ])
            ->findOrFail($id);
    }

    /**
     * Get next sequence number for letter type code in a year.
     * 
     * @param string $letterTypeCode Kode jenis surat (01-16)
     * @param int $institutionId ID institusi
     * @param int $year Tahun
     * @param string $type Tipe surat (keluar atau internal)
     * @return int Nomor urut berikutnya (1, 2, 3, dst)
     */
    public function getNextSequenceNumber(string $letterTypeCode, int $institutionId, int $year, string $type = 'keluar'): int
    {
        // Get the last correspondence with same letter_type_code, type, and year
        $lastCorrespondence = $this->query()
            ->where('institution_id', $institutionId)
            ->where('type', $type) // Surat keluar atau internal
            ->where('letter_type_code', $letterTypeCode)
            ->whereYear('date', $year)
            ->whereNotNull('letter_number')
            ->orderBy('letter_number', 'desc')
            ->first();

        if ($lastCorrespondence && $lastCorrespondence->letter_number) {
            // Extract sequence number from letter number
            // Format: KK-NNN/JS/{NPSN}/BLN/TAHUN
            // Example: 01-001/SK/12345678/II/2026
            preg_match('/^(\d{2})-(\d{3})\//', $lastCorrespondence->letter_number, $matches);
            if (isset($matches[2])) {
                $lastSequence = (int)$matches[2];
                return $lastSequence + 1;
            }
        }

        return 1; // Start from 1 if no previous letter
    }
}

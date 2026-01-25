<?php

namespace App\Services;

use App\Models\Correspondence;
use App\Models\CorrespondenceHistory;
use App\Repositories\CorrespondenceRepository;
use App\Helpers\LetterHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CorrespondenceService
{
    public function __construct(
        private CorrespondenceRepository $repository
    ) {}

    /**
     * Get list of correspondence with filters.
     */
    public function list(array $filters, ?int $institutionId = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->list($filters, $institutionId, $perPage);
    }

    /**
     * Create a new correspondence.
     */
    public function create(array $data, int $userId, ?\Illuminate\Http\UploadedFile $file = null): Correspondence
    {
        return DB::transaction(function () use ($data, $userId, $file) {
            $data['created_by'] = $userId;
            
            // Auto-generate letter number for surat keluar dan internal
            if (in_array($data['type'], ['keluar', 'internal']) && !empty($data['letter_type_code']) && empty($data['letter_number'])) {
                $institution = \App\Models\Institution::find($data['institution_id']);
                if (!$institution) {
                    throw new \Exception('Institusi tidak ditemukan');
                }
                
                if (empty($institution->npsn)) {
                    throw new \Exception('NPSN institusi belum diatur. Silakan lengkapi data institusi terlebih dahulu.');
                }
                
                if (empty($data['date'])) {
                    throw new \Exception('Tanggal surat wajib diisi untuk surat ' . $data['type']);
                }
                
                try {
                    $date = new \DateTime($data['date']);
                    $year = (int)$date->format('Y');
                    
                    // Get next sequence number
                    $sequenceNumber = $this->repository->getNextSequenceNumber(
                        $data['letter_type_code'],
                        $data['institution_id'],
                        $year,
                        $data['type']
                    );
                    
                    // Generate letter number
                    $data['letter_number'] = LetterHelper::generateLetterNumber(
                        $data['letter_type_code'],
                        $sequenceNumber,
                        $institution->npsn,
                        $date
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to generate letter number', [
                        'error' => $e->getMessage(),
                        'data' => $data,
                    ]);
                    throw new \Exception('Gagal menghasilkan nomor surat: ' . $e->getMessage());
                }
            }

            // Handle file upload
            if ($file) {
                try {
                    // Sanitize file name to prevent path traversal
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                    $fileName = time() . '_' . $safeName . '.' . $extension;
                    $filePath = $file->storeAs(
                        'correspondence/' . $data['institution_id'],
                        $fileName,
                        'public'
                    );
                    $data['file_path'] = $filePath;
                    $data['file_name'] = $originalName; // Keep original name for display
                } catch (\Exception $e) {
                    Log::error('Failed to upload file', [
                        'error' => $e->getMessage(),
                    ]);
                    throw new \Exception('Gagal mengunggah file: ' . $e->getMessage());
                }
            }

            try {
                $correspondence = Correspondence::create($data);
            } catch (\Illuminate\Database\QueryException $e) {
                Log::error('Failed to create correspondence', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                ]);
                
                // Check for specific database errors
                if ($e->getCode() === '23000') {
                    throw new \Exception('Data surat tidak valid atau duplikat');
                }
                
                throw new \Exception('Gagal menyimpan surat: ' . $e->getMessage());
            } catch (\Exception $e) {
                Log::error('Failed to create correspondence', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                ]);
                throw $e;
            }

            // Create history
            try {
                $this->createHistory($correspondence->id, $userId, 'created', 'Surat dibuat');
            } catch (\Exception $e) {
                // Log but don't fail the transaction if history creation fails
                Log::warning('Failed to create history', [
                    'error' => $e->getMessage(),
                    'correspondence_id' => $correspondence->id,
                ]);
            }

            Log::info('Correspondence created', [
                'correspondence_id' => $correspondence->id,
                'institution_id' => $correspondence->institution_id,
                'type' => $correspondence->type,
                'letter_type_code' => $correspondence->letter_type_code,
                'letter_number' => $correspondence->letter_number,
            ]);

            return $correspondence->load(['category', 'creator', 'institution']);
        });
    }

    /**
     * Get correspondence by ID.
     */
    public function find(int $id): Correspondence
    {
        return $this->repository->findWithRelations($id);
    }

    /**
     * Update correspondence.
     */
    public function update(Correspondence $correspondence, array $data, int $userId, ?\Illuminate\Http\UploadedFile $file = null): Correspondence
    {
        return DB::transaction(function () use ($correspondence, $data, $userId, $file) {
            $oldData = $correspondence->toArray();

            // Handle regenerating letter number for surat keluar dan internal if type, letter_type_code, or date changed
            if (in_array($correspondence->type, ['keluar', 'internal']) && 
                isset($data['letter_type_code']) && 
                isset($data['date']) &&
                ($correspondence->letter_type_code !== $data['letter_type_code'] || 
                 $correspondence->date->format('Y-m-d') !== $data['date'])) {
                
                // Only regenerate if letter_number was auto-generated (starts with format KK-NNN/)
                if ($correspondence->letter_number && preg_match('/^\d{2}-\d{3}\//', $correspondence->letter_number)) {
                    $institution = $correspondence->institution;
                    if ($institution && $institution->npsn) {
                        $date = new \DateTime($data['date']);
                        $year = (int)$date->format('Y');
                        
                        // Get next sequence number
                        $sequenceNumber = $this->repository->getNextSequenceNumber(
                            $data['letter_type_code'],
                            $correspondence->institution_id,
                            $year,
                            $correspondence->type
                        );
                        
                        // Generate new letter number
                        $data['letter_number'] = LetterHelper::generateLetterNumber(
                            $data['letter_type_code'],
                            $sequenceNumber,
                            $institution->npsn,
                            $date
                        );
                    }
                }
            }

            // Handle file upload
            if ($file) {
                // Delete old file if exists
                if ($correspondence->file_path && Storage::disk('public')->exists($correspondence->file_path)) {
                    Storage::disk('public')->delete($correspondence->file_path);
                }

                // Sanitize file name to prevent path traversal
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                $fileName = time() . '_' . $safeName . '.' . $extension;
                $filePath = $file->storeAs(
                    'correspondence/' . $correspondence->institution_id,
                    $fileName,
                    'public'
                );
                $data['file_path'] = $filePath;
                $data['file_name'] = $originalName; // Keep original name for display
            }

            $correspondence->update($data);

            // Create history with changes
            $changes = array_diff_assoc($data, $oldData);
            $this->createHistory($correspondence->id, $userId, 'updated', 'Surat diperbarui', $changes);

            Log::info('Correspondence updated', [
                'correspondence_id' => $correspondence->id,
            ]);

            return $correspondence->fresh(['category', 'creator', 'approver', 'institution']);
        });
    }

    /**
     * Delete correspondence (soft delete).
     */
    public function delete(Correspondence $correspondence, int $userId): bool
    {
        return DB::transaction(function () use ($correspondence, $userId) {
            $correspondenceId = $correspondence->id;

            $result = $correspondence->delete();

            // Create history
            $this->createHistory($correspondenceId, $userId, 'deleted', 'Surat dihapus');

            Log::info('Correspondence deleted', [
                'correspondence_id' => $correspondenceId,
            ]);

            return $result;
        });
    }

    /**
     * Approve correspondence.
     */
    public function approve(Correspondence $correspondence, int $userId): Correspondence
    {
        if (!$correspondence->canBeApproved()) {
            throw new \Exception('Surat tidak dapat disetujui');
        }

        return DB::transaction(function () use ($correspondence, $userId) {
            $correspondence->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            $this->createHistory($correspondence->id, $userId, 'approved', 'Surat disetujui');

            Log::info('Correspondence approved', [
                'correspondence_id' => $correspondence->id,
                'approved_by' => $userId,
            ]);

            return $correspondence->fresh(['approver']);
        });
    }

    /**
     * Send correspondence.
     */
    public function send(Correspondence $correspondence, int $userId): Correspondence
    {
        if (!$correspondence->canBeSent()) {
            throw new \Exception('Surat tidak dapat dikirim');
        }

        return DB::transaction(function () use ($correspondence, $userId) {
            $correspondence->update([
                'status' => 'sent',
            ]);

            $this->createHistory($correspondence->id, $userId, 'sent', 'Surat dikirim');

            Log::info('Correspondence sent', [
                'correspondence_id' => $correspondence->id,
            ]);

            return $correspondence->fresh();
        });
    }

    /**
     * Archive correspondence.
     */
    public function archive(Correspondence $correspondence, int $userId): Correspondence
    {
        return DB::transaction(function () use ($correspondence, $userId) {
            $correspondence->update([
                'status' => 'archived',
            ]);

            $this->createHistory($correspondence->id, $userId, 'archived', 'Surat diarsipkan');

            Log::info('Correspondence archived', [
                'correspondence_id' => $correspondence->id,
            ]);

            return $correspondence->fresh();
        });
    }

    /**
     * Create history record.
     */
    private function createHistory(int $correspondenceId, int $userId, string $action, string $notes = null, array $changes = null): void
    {
        CorrespondenceHistory::create([
            'correspondence_id' => $correspondenceId,
            'user_id' => $userId,
            'action' => $action,
            'notes' => $notes,
            'changes' => $changes,
        ]);
    }
}

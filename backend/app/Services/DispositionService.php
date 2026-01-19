<?php

namespace App\Services;

use App\Models\Correspondence;
use App\Models\CorrespondenceDisposition;
use App\Models\CorrespondenceHistory;
use App\Notifications\DispositionNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DispositionService
{
    /**
     * Create a new disposition.
     */
    public function create(Correspondence $correspondence, array $data, int $fromUserId): CorrespondenceDisposition
    {
        return DB::transaction(function () use ($correspondence, $data, $fromUserId) {
            // Validate that correspondence belongs to same institution as users
            $toUserId = $data['to_user_id'];
            $toUser = \App\Models\User::findOrFail($toUserId);
            
            if ($correspondence->institution_id !== $toUser->institution_id) {
                throw new \Exception('User penerima harus dari institusi yang sama');
            }

            $disposition = CorrespondenceDisposition::create([
                'correspondence_id' => $correspondence->id,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'instruction' => $data['instruction'],
                'status' => 'pending',
            ]);

            // Create history for correspondence
            $this->createCorrespondenceHistory(
                $correspondence->id,
                $fromUserId,
                'disposed',
                "Disposisi diberikan kepada {$toUser->name}",
                ['disposition_id' => $disposition->id]
            );

            // Send notification to recipient
            try {
                $toUser->notify(new DispositionNotification($disposition, 'created'));
            } catch (\Exception $e) {
                Log::warning('Failed to send disposition notification', [
                    'error' => $e->getMessage(),
                    'disposition_id' => $disposition->id,
                    'to_user_id' => $toUserId,
                ]);
            }

            Log::info('Disposition created', [
                'disposition_id' => $disposition->id,
                'correspondence_id' => $correspondence->id,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
            ]);

            return $disposition->load(['fromUser', 'toUser', 'correspondence']);
        });
    }

    /**
     * Get dispositions for a correspondence.
     */
    public function getByCorrespondence(int $correspondenceId): \Illuminate\Database\Eloquent\Collection
    {
        return CorrespondenceDisposition::with(['fromUser', 'toUser'])
            ->where('correspondence_id', $correspondenceId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get pending dispositions for a user.
     */
    public function getPendingForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return CorrespondenceDisposition::with(['fromUser', 'correspondence'])
            ->where('to_user_id', $userId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Complete a disposition.
     */
    public function complete(CorrespondenceDisposition $disposition, int $userId, ?string $notes = null): CorrespondenceDisposition
    {
        if ($disposition->to_user_id !== $userId) {
            throw new \Exception('Anda tidak memiliki izin untuk menyelesaikan disposisi ini');
        }

        if ($disposition->status === 'completed') {
            throw new \Exception('Disposisi sudah diselesaikan');
        }

        return DB::transaction(function () use ($disposition, $userId, $notes) {
            $disposition->markAsCompleted();
            $fromUser = $disposition->fromUser;

            // Create history for correspondence
            $this->createCorrespondenceHistory(
                $disposition->correspondence_id,
                $userId,
                'disposition_completed',
                $notes ?? 'Disposisi diselesaikan',
                ['disposition_id' => $disposition->id]
            );

            // Send notification to the user who created the disposition
            try {
                $fromUser->notify(new DispositionNotification($disposition->fresh(), 'completed'));
            } catch (\Exception $e) {
                Log::warning('Failed to send disposition completion notification', [
                    'error' => $e->getMessage(),
                    'disposition_id' => $disposition->id,
                    'from_user_id' => $fromUser->id,
                ]);
            }

            Log::info('Disposition completed', [
                'disposition_id' => $disposition->id,
                'completed_by' => $userId,
            ]);

            return $disposition->fresh(['fromUser', 'toUser', 'correspondence']);
        });
    }

    /**
     * Update a disposition.
     */
    public function update(CorrespondenceDisposition $disposition, array $data, int $userId): CorrespondenceDisposition
    {
        // Only from_user can update, and only if not completed
        if ($disposition->from_user_id !== $userId) {
            throw new \Exception('Anda tidak memiliki izin untuk mengubah disposisi ini');
        }

        if ($disposition->status === 'completed') {
            throw new \Exception('Disposisi yang sudah diselesaikan tidak dapat diubah');
        }

        return DB::transaction(function () use ($disposition, $data, $userId) {
            $oldData = $disposition->toArray();

            // Update allowed fields
            if (isset($data['to_user_id'])) {
                $toUser = \App\Models\User::findOrFail($data['to_user_id']);
                if ($disposition->correspondence->institution_id !== $toUser->institution_id) {
                    throw new \Exception('User penerima harus dari institusi yang sama');
                }
                $disposition->to_user_id = $data['to_user_id'];
            }

            if (isset($data['instruction'])) {
                $disposition->instruction = $data['instruction'];
            }

            $disposition->save();
            $toUser = $disposition->toUser;

            // Create history
            $changes = array_diff_assoc($disposition->toArray(), $oldData);
            $this->createCorrespondenceHistory(
                $disposition->correspondence_id,
                $userId,
                'disposition_updated',
                'Disposisi diperbarui',
                ['disposition_id' => $disposition->id, 'changes' => $changes]
            );

            // Send notification to recipient if disposition was updated
            try {
                $toUser->notify(new DispositionNotification($disposition->fresh(), 'updated'));
            } catch (\Exception $e) {
                Log::warning('Failed to send disposition update notification', [
                    'error' => $e->getMessage(),
                    'disposition_id' => $disposition->id,
                    'to_user_id' => $toUser->id,
                ]);
            }

            Log::info('Disposition updated', [
                'disposition_id' => $disposition->id,
                'updated_by' => $userId,
            ]);

            return $disposition->fresh(['fromUser', 'toUser', 'correspondence']);
        });
    }

    /**
     * Delete a disposition.
     */
    public function delete(CorrespondenceDisposition $disposition, int $userId): bool
    {
        // Only from_user can delete, and only if not completed
        if ($disposition->from_user_id !== $userId) {
            throw new \Exception('Anda tidak memiliki izin untuk menghapus disposisi ini');
        }

        if ($disposition->status === 'completed') {
            throw new \Exception('Disposisi yang sudah diselesaikan tidak dapat dihapus');
        }

        return DB::transaction(function () use ($disposition, $userId) {
            $correspondenceId = $disposition->correspondence_id;
            $result = $disposition->delete();

            // Create history
            $this->createCorrespondenceHistory(
                $correspondenceId,
                $userId,
                'disposition_deleted',
                'Disposisi dihapus',
                ['disposition_id' => $disposition->id]
            );

            Log::info('Disposition deleted', [
                'disposition_id' => $disposition->id,
                'deleted_by' => $userId,
            ]);

            return $result;
        });
    }

    /**
     * Create correspondence history record.
     */
    private function createCorrespondenceHistory(
        int $correspondenceId,
        int $userId,
        string $action,
        string $notes = null,
        array $changes = null
    ): void {
        try {
            CorrespondenceHistory::create([
                'correspondence_id' => $correspondenceId,
                'user_id' => $userId,
                'action' => $action,
                'notes' => $notes,
                'changes' => $changes,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to create correspondence history', [
                'error' => $e->getMessage(),
                'correspondence_id' => $correspondenceId,
            ]);
        }
    }
}

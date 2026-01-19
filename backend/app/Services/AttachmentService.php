<?php

namespace App\Services;

use App\Models\Correspondence;
use App\Models\CorrespondenceAttachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AttachmentService
{
    /**
     * Upload multiple attachments for a correspondence.
     */
    public function uploadAttachments(Correspondence $correspondence, array $files, ?int $userId = null): array
    {
        $uploadedAttachments = [];

        foreach ($files as $file) {
            try {
                $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs(
                    'correspondence/' . $correspondence->institution_id . '/attachments',
                    $fileName,
                    'public'
                );

                $attachment = CorrespondenceAttachment::create([
                    'correspondence_id' => $correspondence->id,
                    'file_path' => $filePath,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);

                $uploadedAttachments[] = $attachment;

                Log::info('Attachment uploaded', [
                    'attachment_id' => $attachment->id,
                    'correspondence_id' => $correspondence->id,
                    'file_name' => $file->getClientOriginalName(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to upload attachment', [
                    'error' => $e->getMessage(),
                    'correspondence_id' => $correspondence->id,
                    'file_name' => $file->getClientOriginalName(),
                ]);
                throw new \Exception('Gagal mengunggah file: ' . $file->getClientOriginalName() . ' - ' . $e->getMessage());
            }
        }

        return $uploadedAttachments;
    }

    /**
     * Delete an attachment.
     */
    public function deleteAttachment(CorrespondenceAttachment $attachment, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($attachment, $userId) {
            // Delete file from storage
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            $attachmentId = $attachment->id;
            $correspondenceId = $attachment->correspondence_id;
            $result = $attachment->delete();

            Log::info('Attachment deleted', [
                'attachment_id' => $attachmentId,
                'correspondence_id' => $correspondenceId,
                'deleted_by' => $userId,
            ]);

            return $result;
        });
    }

    /**
     * Get attachments for a correspondence.
     */
    public function getAttachments(int $correspondenceId): \Illuminate\Database\Eloquent\Collection
    {
        return CorrespondenceAttachment::where('correspondence_id', $correspondenceId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Update attachment description.
     */
    public function updateAttachment(CorrespondenceAttachment $attachment, array $data): CorrespondenceAttachment
    {
        if (isset($data['description'])) {
            $attachment->description = $data['description'];
            $attachment->save();
        }

        Log::info('Attachment updated', [
            'attachment_id' => $attachment->id,
        ]);

        return $attachment->fresh();
    }
}

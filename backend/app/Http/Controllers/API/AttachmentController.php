<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Correspondence;
use App\Models\CorrespondenceAttachment;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function __construct(
        private AttachmentService $service
    ) {}

    /**
     * Get attachments for a correspondence.
     */
    public function index(Request $request, int $correspondenceId)
    {
        try {
            $correspondence = Correspondence::findOrFail($correspondenceId);

            $this->authorize('view', $correspondence);

            $attachments = $this->service->getAttachments($correspondenceId);

            return AttachmentResource::collection($attachments);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return ApiResponse::forbidden($e->getMessage() ?: null);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Surat tidak ditemukan');
        } catch (\Exception $e) {
            Log::error('Failed to list attachments', [
                'error' => $e->getMessage(),
                'correspondence_id' => $correspondenceId,
            ]);
            return ApiResponse::serverError('Terjadi kesalahan saat mengambil data lampiran', $e->getMessage());
        }
    }

    /**
     * Upload attachments for a correspondence.
     */
    public function store(Request $request, int $correspondenceId)
    {
        try {
            // Use standardized file upload validation
            $rules = \App\Helpers\FileUploadRules::correspondenceAttachments();
            $messages = \App\Helpers\FileUploadRules::messages(
                \App\Helpers\FileUploadRules::TYPE_MIXED,
                \App\Helpers\FileUploadRules::SIZE_LARGE,
                'files',
                true
            );
            
            $request->validate($rules, $messages);

            $correspondence = Correspondence::findOrFail($correspondenceId);

            $this->authorize('update', $correspondence);

            $files = $request->file('files');
            $attachments = $this->service->uploadAttachments($correspondence, $files, $request->user()->id);

            return response()->json([
                'message' => 'Lampiran berhasil diunggah',
                'data' => AttachmentResource::collection($attachments),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::validationFailed($e->errors());
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return ApiResponse::forbidden($e->getMessage() ?: null);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Surat tidak ditemukan');
        } catch (\Exception $e) {
            Log::error('Failed to upload attachments', [
                'error' => $e->getMessage(),
                'correspondence_id' => $correspondenceId,
            ]);
            return ApiResponse::serverError($e->getMessage() ?: 'Terjadi kesalahan saat mengunggah lampiran', $e->getMessage());
        }
    }

    /**
     * Download an attachment.
     */
    public function download(Request $request, int $id)
    {
        try {
            $attachment = CorrespondenceAttachment::findOrFail($id);
            $correspondence = $attachment->correspondence;

            $this->authorize('view', $correspondence);

            if (!Storage::disk('public')->exists($attachment->file_path)) {
                return ApiResponse::notFound('File tidak ditemukan');
            }

            return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return ApiResponse::forbidden($e->getMessage() ?: null);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Lampiran tidak ditemukan');
        } catch (\Exception $e) {
            Log::error('Failed to download attachment', [
                'error' => $e->getMessage(),
                'attachment_id' => $id,
            ]);
            return ApiResponse::serverError('Terjadi kesalahan saat mengunduh file', $e->getMessage());
        }
    }

    /**
     * Update an attachment (description only).
     */
    public function update(Request $request, int $id)
    {
        try {
            $request->validate([
                'description' => 'nullable|string|max:500',
            ]);

            $attachment = CorrespondenceAttachment::findOrFail($id);
            $correspondence = $attachment->correspondence;

            $this->authorize('update', $correspondence);

            $attachment = $this->service->updateAttachment($attachment, $request->only('description'));

            return response()->json([
                'message' => 'Lampiran berhasil diperbarui',
                'data' => new AttachmentResource($attachment),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::validationFailed($e->errors());
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return ApiResponse::forbidden($e->getMessage() ?: null);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Lampiran tidak ditemukan');
        } catch (\Exception $e) {
            Log::error('Failed to update attachment', [
                'error' => $e->getMessage(),
                'attachment_id' => $id,
            ]);
            return ApiResponse::serverError('Terjadi kesalahan saat memperbarui lampiran', $e->getMessage());
        }
    }

    /**
     * Delete an attachment.
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $attachment = CorrespondenceAttachment::findOrFail($id);
            $correspondence = $attachment->correspondence;

            $this->authorize('update', $correspondence);

            $this->service->deleteAttachment($attachment, $request->user()->id);

            return response()->json([
                'message' => 'Lampiran berhasil dihapus',
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return ApiResponse::forbidden($e->getMessage() ?: null);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Lampiran tidak ditemukan');
        } catch (\Exception $e) {
            Log::error('Failed to delete attachment', [
                'error' => $e->getMessage(),
                'attachment_id' => $id,
            ]);
            return ApiResponse::serverError('Terjadi kesalahan saat menghapus lampiran', $e->getMessage());
        }
    }
}

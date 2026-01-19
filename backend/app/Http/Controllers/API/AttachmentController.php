<?php

namespace App\Http\Controllers\API;

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

            // Check authorization
            if (!$request->user()->isAdminOrSuperAdmin() && 
                $correspondence->institution_id !== $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $attachments = $this->service->getAttachments($correspondenceId);

            return AttachmentResource::collection($attachments);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to list attachments', [
                'error' => $e->getMessage(),
                'correspondence_id' => $correspondenceId,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data lampiran',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Upload attachments for a correspondence.
     */
    public function store(Request $request, int $correspondenceId)
    {
        try {
            $request->validate([
                'files' => 'required|array|min:1|max:10',
                'files.*' => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
            ], [
                'files.required' => 'Minimal 1 file harus diunggah',
                'files.array' => 'Format file tidak valid',
                'files.min' => 'Minimal 1 file harus diunggah',
                'files.max' => 'Maksimal 10 file dapat diunggah sekaligus',
                'files.*.required' => 'File wajib diisi',
                'files.*.file' => 'File tidak valid',
                'files.*.max' => 'Ukuran file maksimal 10MB',
                'files.*.mimes' => 'Format file harus PDF, DOC, DOCX, JPG, JPEG, atau PNG',
            ]);

            $correspondence = Correspondence::findOrFail($correspondenceId);

            // Check authorization
            if (!$request->user()->isAdminOrSuperAdmin() && 
                $correspondence->institution_id !== $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $files = $request->file('files');
            $attachments = $this->service->uploadAttachments($correspondence, $files, $request->user()->id);

            return response()->json([
                'message' => 'Lampiran berhasil diunggah',
                'data' => AttachmentResource::collection($attachments),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to upload attachments', [
                'error' => $e->getMessage(),
                'correspondence_id' => $correspondenceId,
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat mengunggah lampiran',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
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

            // Check authorization
            if (!$request->user()->isAdminOrSuperAdmin() && 
                $correspondence->institution_id !== $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (!Storage::disk('public')->exists($attachment->file_path)) {
                return response()->json(['message' => 'File tidak ditemukan'], 404);
            }

            return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Lampiran tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to download attachment', [
                'error' => $e->getMessage(),
                'attachment_id' => $id,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengunduh file',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
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

            // Check authorization
            if (!$request->user()->isAdminOrSuperAdmin() && 
                $correspondence->institution_id !== $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $attachment = $this->service->updateAttachment($attachment, $request->only('description'));

            return response()->json([
                'message' => 'Lampiran berhasil diperbarui',
                'data' => new AttachmentResource($attachment),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Lampiran tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update attachment', [
                'error' => $e->getMessage(),
                'attachment_id' => $id,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui lampiran',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
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

            // Check authorization
            if (!$request->user()->isAdminOrSuperAdmin() && 
                $correspondence->institution_id !== $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $this->service->deleteAttachment($attachment, $request->user()->id);

            return response()->json([
                'message' => 'Lampiran berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Lampiran tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete attachment', [
                'error' => $e->getMessage(),
                'attachment_id' => $id,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus lampiran',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

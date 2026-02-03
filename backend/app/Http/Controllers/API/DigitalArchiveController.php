<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDigitalArchiveCategoryRequest;
use App\Http\Requests\StoreDigitalArchiveRequest;
use App\Http\Requests\UpdateDigitalArchiveRequest;
use App\Http\Resources\DigitalArchiveCategoryResource;
use App\Http\Resources\DigitalArchiveResource;
use App\Models\DigitalArchive;
use App\Services\DigitalArchiveService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalArchiveController extends Controller
{
    public function __construct(
        protected DigitalArchiveService $service
    ) {}

    protected function getInstitutionId(Request $request): ?int
    {
        if ($request->user()->isAdminOrSuperAdmin() && $request->has('institution_id')) {
            return (int) $request->institution_id;
        }
        return $request->user()->institution_id;
    }

    /**
     * List digital archives.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['search', 'category_id', 'date_from', 'date_to']);
            $perPage = min($request->get('per_page', 15), 100);

            $archives = $this->service->list($filters, $institutionId, $perPage);
            return DigitalArchiveResource::collection($archives);
        } catch (\Exception $e) {
            Log::error('DigitalArchive index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data arsip digital.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a new archive (with file upload).
     */
    public function store(StoreDigitalArchiveRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            unset($data['file']);
            $file = $request->file('file');

            $archive = $this->service->create($data, $institutionId, $request->user()->id, $file);
            $archive->load(['category', 'creator']);

            return response()->json([
                'message' => 'Dokumen berhasil diarsipkan.',
                'data' => new DigitalArchiveResource($archive),
            ], 201);
        } catch (\Exception $e) {
            Log::error('DigitalArchive store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengunggah dokumen. ' . ($e->getMessage()),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show single archive.
     */
    public function show(Request $request, DigitalArchive $digital_archive): DigitalArchiveResource|JsonResponse
    {
        $institutionId = $this->getInstitutionId($request);
        if ($institutionId && (int) $digital_archive->institution_id !== $institutionId && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $digital_archive->load(['category', 'creator']);
        return new DigitalArchiveResource($digital_archive);
    }

    /**
     * Update archive (file optional).
     */
    public function update(UpdateDigitalArchiveRequest $request, DigitalArchive $digital_archive): JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if ($institutionId && (int) $digital_archive->institution_id !== $institutionId && !$request->user()->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $request->validated();
            unset($data['file']);
            $file = $request->file('file');

            $archive = $this->service->update($digital_archive, $data, $file);

            return response()->json([
                'message' => 'Arsip berhasil diperbarui.',
                'data' => new DigitalArchiveResource($archive),
            ], 200);
        } catch (\Exception $e) {
            Log::error('DigitalArchive update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui arsip.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete archive (soft delete).
     */
    public function destroy(Request $request, DigitalArchive $digital_archive): JsonResponse
    {
        $institutionId = $this->getInstitutionId($request);
        if ($institutionId && (int) $digital_archive->institution_id !== $institutionId && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->service->delete($digital_archive);
        return response()->json(['message' => 'Arsip berhasil dihapus.'], 200);
    }

    /**
     * Download file.
     */
    public function download(Request $request, DigitalArchive $digital_archive): StreamedResponse|JsonResponse
    {
        $institutionId = $this->getInstitutionId($request);
        if ($institutionId && (int) $digital_archive->institution_id !== $institutionId && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$digital_archive->file_path || !Storage::disk('public')->exists($digital_archive->file_path)) {
            return response()->json(['message' => 'File tidak ditemukan.'], 404);
        }

        return Storage::disk('public')->download(
            $digital_archive->file_path,
            $digital_archive->file_name,
            ['Content-Type' => $digital_archive->mime_type ?? 'application/octet-stream']
        );
    }

    /**
     * List categories for current institution.
     */
    public function categories(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $categories = $this->service->listCategories($institutionId);
            return DigitalArchiveCategoryResource::collection($categories);
        } catch (\Exception $e) {
            Log::error('DigitalArchive categories failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil kategori.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a new category.
     */
    public function storeCategory(StoreDigitalArchiveCategoryRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $category = $this->service->createCategory($request->validated(), $institutionId);
            return response()->json([
                'message' => 'Kategori berhasil ditambah.',
                'data' => new DigitalArchiveCategoryResource($category),
            ], 201);
        } catch (\Exception $e) {
            Log::error('DigitalArchive storeCategory failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menambah kategori.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentPickupRequest;
use App\Http\Requests\UpdateDocumentPickupRequest;
use App\Http\Resources\DocumentPickupResource;
use App\Models\DocumentPickup;
use App\Services\DocumentPickupService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class DocumentPickupController extends Controller
{
    public function __construct(
        protected DocumentPickupService $service
    ) {}

    protected function getInstitutionId(Request $request): ?int
    {
        if ($request->user()->isAdminOrSuperAdmin() && $request->has('institution_id')) {
            return (int) $request->institution_id;
        }
        return $request->user()->institution_id;
    }

    /**
     * List document pickups (pengambilan ijazah).
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['search', 'date_from', 'date_to', 'student_id']);
            $perPage = min($request->get('per_page', 15), 100);

            $list = $this->service->list($filters, $institutionId, $perPage);
            return DocumentPickupResource::collection($list);
        } catch (\Exception $e) {
            Log::error('DocumentPickup index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data pengambilan ijazah.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store new document pickup (optional photo).
     */
    public function store(StoreDocumentPickupRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            unset($data['foto']);
            $file = $request->file('foto');

            $pickup = $this->service->create($data, $institutionId, $request->user()->id, $file);
            $pickup->load(['student', 'student.class', 'creator']);

            return response()->json([
                'message' => 'Pengambilan ijazah berhasil dicatat.',
                'data' => new DocumentPickupResource($pickup),
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('DocumentPickup store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencatat pengambilan ijazah.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show single document pickup.
     */
    public function show(Request $request, DocumentPickup $document_pickup): DocumentPickupResource|JsonResponse
    {
        $institutionId = $this->getInstitutionId($request);
        if ($institutionId && (int) $document_pickup->institution_id !== $institutionId && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $document_pickup->load(['student', 'student.class', 'creator']);
        return new DocumentPickupResource($document_pickup);
    }

    /**
     * Update document pickup (photo optional).
     */
    public function update(UpdateDocumentPickupRequest $request, DocumentPickup $document_pickup): JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if ($institutionId && (int) $document_pickup->institution_id !== $institutionId && !$request->user()->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $request->validated();
            unset($data['foto']);
            $file = $request->file('foto');

            $pickup = $this->service->update($document_pickup, $data, $file);

            return response()->json([
                'message' => 'Data pengambilan ijazah berhasil diperbarui.',
                'data' => new DocumentPickupResource($pickup),
            ], 200);
        } catch (\Exception $e) {
            Log::error('DocumentPickup update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui data pengambilan ijazah.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete document pickup (soft delete).
     */
    public function destroy(Request $request, DocumentPickup $document_pickup): JsonResponse
    {
        $institutionId = $this->getInstitutionId($request);
        if ($institutionId && (int) $document_pickup->institution_id !== $institutionId && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->service->delete($document_pickup);
        return response()->json(['message' => 'Data pengambilan ijazah berhasil dihapus.'], 200);
    }
}

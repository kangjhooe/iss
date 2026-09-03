<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StoreGuestVisitRequest;
use App\Http\Requests\UpdateGuestVisitRequest;
use App\Http\Resources\GuestVisitResource;
use App\Models\GuestVisit;
use App\Services\GuestVisitService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GuestVisitController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected GuestVisitService $service
    ) {}

    protected function getInstitutionId(Request $request): ?int
    {
        return $this->resolveInstitutionId($request);
    }

    /**
     * List guest visits (buku tamu).
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['search', 'date_from', 'date_to']);
            $perPage = min($request->get('per_page', 15), 100);

            $visits = $this->service->list($filters, $institutionId, $perPage);
            return GuestVisitResource::collection($visits);
        } catch (\Exception $e) {
            Log::error('GuestVisit index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data buku tamu.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store new guest visit (with photo).
     */
    public function store(StoreGuestVisitRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            unset($data['foto']);
            $file = $request->file('foto');

            if (!$file) {
                return response()->json(['message' => 'Foto tamu wajib diambil/diunggah.'], 422);
            }

            $visit = $this->service->create($data, $institutionId, $request->user()->id, $file);
            $visit->load('creator');

            return response()->json([
                'message' => 'Buku tamu berhasil dicatat.',
                'data' => new GuestVisitResource($visit),
            ], 201);
        } catch (\Exception $e) {
            Log::error('GuestVisit store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencatat buku tamu. ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show single guest visit.
     */
    public function show(Request $request, GuestVisit $guest_visit): GuestVisitResource|JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $guest_visit->institution_id, 'Unauthorized')) {
            return $resp;
        }

        $guest_visit->load('creator');
        return new GuestVisitResource($guest_visit);
    }

    /**
     * Update guest visit (photo optional).
     */
    public function update(UpdateGuestVisitRequest $request, GuestVisit $guest_visit): JsonResponse
    {
        try {
            if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $guest_visit->institution_id, 'Unauthorized')) {
                return $resp;
            }

            $data = $request->validated();
            unset($data['foto']);
            $file = $request->file('foto');

            $visit = $this->service->update($guest_visit, $data, $file);

            return response()->json([
                'message' => 'Data tamu berhasil diperbarui.',
                'data' => new GuestVisitResource($visit),
            ], 200);
        } catch (\Exception $e) {
            Log::error('GuestVisit update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui data tamu.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete guest visit (soft delete).
     */
    public function destroy(Request $request, GuestVisit $guest_visit): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $guest_visit->institution_id, 'Unauthorized')) {
            return $resp;
        }

        $this->service->delete($guest_visit);
        return response()->json(['message' => 'Data tamu berhasil dihapus.'], 200);
    }

    /**
     * Set waktu keluar (check-out tamu).
     */
    public function checkout(Request $request, GuestVisit $guest_visit): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $guest_visit->institution_id, 'Unauthorized')) {
            return $resp;
        }

        $visit = $this->service->setWaktuKeluar($guest_visit);
        return response()->json([
            'message' => 'Waktu keluar berhasil dicatat.',
            'data' => new GuestVisitResource($visit),
        ], 200);
    }

    /**
     * Export data buku tamu (JSON) untuk cetak/preview di frontend.
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['search', 'date_from', 'date_to']);
            $visits = $this->service->listForExport($filters, $institutionId);

            return response()->json([
                'message' => 'OK',
                'data' => GuestVisitResource::collection($visits),
            ]);
        } catch (\Exception $e) {
            Log::error('GuestVisit export failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data export.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

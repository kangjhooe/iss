<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUksVisitTypeRequest;
use App\Http\Requests\UpdateUksVisitTypeRequest;
use App\Http\Resources\UksVisitTypeResource;
use App\Models\UksVisitType;
use App\Services\UksService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class UksVisitTypeController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected UksService $uksService
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $activeOnly = filter_var($request->get('active_only', true), FILTER_VALIDATE_BOOLEAN);
            $types = $this->uksService->listTypes($institutionId, $activeOnly);

            return UksVisitTypeResource::collection($types);
        } catch (\Exception $e) {
            Log::error('UKS type index failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil jenis kunjungan UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreUksVisitTypeRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $data['is_active'] = $data['is_active'] ?? true;
            $type = UksVisitType::create($data);

            return (new UksVisitTypeResource($type))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('UKS type store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menambahkan jenis kunjungan UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function seedDefaults(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $types = $this->uksService->seedDefaultTypes($institutionId);

            return UksVisitTypeResource::collection($types);
        } catch (\Exception $e) {
            Log::error('UKS seed defaults failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengisi jenis kunjungan standar.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, UksVisitType $uks_visit_type): UksVisitTypeResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $uks_visit_type->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new UksVisitTypeResource($uks_visit_type);
    }

    public function update(UpdateUksVisitTypeRequest $request, UksVisitType $uks_visit_type): UksVisitTypeResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $uks_visit_type->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $uks_visit_type->update($request->validated());

            return new UksVisitTypeResource($uks_visit_type->fresh());
        } catch (\Exception $e) {
            Log::error('UKS type update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memperbarui jenis kunjungan UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, UksVisitType $uks_visit_type): JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $uks_visit_type->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($uks_visit_type->visits()->exists()) {
            $uks_visit_type->update(['is_active' => false]);

            return response()->json(['message' => 'Jenis kunjungan dinonaktifkan karena masih dipakai data kunjungan.']);
        }

        $uks_visit_type->delete();

        return response()->json(['message' => 'Jenis kunjungan berhasil dihapus.']);
    }
}

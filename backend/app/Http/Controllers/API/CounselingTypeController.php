<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCounselingTypeRequest;
use App\Http\Requests\UpdateCounselingTypeRequest;
use App\Http\Resources\CounselingTypeResource;
use App\Models\CounselingType;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class CounselingTypeController extends Controller
{
    use ResolvesInstitution;

    /**
     * List counseling types for current institution.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $activeOnly = filter_var($request->get('active_only', true), FILTER_VALIDATE_BOOLEAN);
            $types = CounselingType::forInstitution($institutionId)
                ->when($activeOnly, fn ($q) => $q->active())
                ->orderBy('name')
                ->get();

            return CounselingTypeResource::collection($types);
        } catch (\Exception $e) {
            Log::error('CounselingType index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data jenis konseling.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a new counseling type.
     */
    public function store(StoreCounselingTypeRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $type = CounselingType::create($data);

            return (new CounselingTypeResource($type))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('CounselingType store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menambahkan jenis konseling.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show single counseling type.
     */
    public function show(Request $request, CounselingType $counseling_type): CounselingTypeResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $counseling_type->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return new CounselingTypeResource($counseling_type);
    }

    /**
     * Update counseling type.
     */
    public function update(UpdateCounselingTypeRequest $request, CounselingType $counseling_type): CounselingTypeResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $counseling_type->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $counseling_type->update($request->validated());
            return new CounselingTypeResource($counseling_type->fresh());
        } catch (\Exception $e) {
            Log::error('CounselingType update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui jenis konseling.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete counseling type.
     */
    public function destroy(Request $request, CounselingType $counseling_type): JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $counseling_type->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($counseling_type->counselingSessions()->exists()) {
            return response()->json([
                'message' => 'Jenis konseling tidak dapat dihapus karena sudah digunakan pada catatan konseling.',
            ], 422);
        }

        $counseling_type->delete();
        return response()->json(['message' => 'Jenis konseling berhasil dihapus.']);
    }
}

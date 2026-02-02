<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreViolationTypeRequest;
use App\Http\Requests\UpdateViolationTypeRequest;
use App\Http\Resources\ViolationTypeResource;
use App\Models\ViolationType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class ViolationTypeController extends Controller
{
    /**
     * List violation types for current institution.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $activeOnly = filter_var($request->get('active_only', true), FILTER_VALIDATE_BOOLEAN);
            $types = ViolationType::forInstitution($institutionId)
                ->when($activeOnly, fn ($q) => $q->active())
                ->orderBy('category')
                ->orderBy('name')
                ->get();

            return ViolationTypeResource::collection($types);
        } catch (\Exception $e) {
            Log::error('ViolationType index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data jenis pelanggaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a new violation type.
     */
    public function store(StoreViolationTypeRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $data['point_weight'] = $data['point_weight'] ?? 0;
            $type = ViolationType::create($data);

            return (new ViolationTypeResource($type))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('ViolationType store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menambahkan jenis pelanggaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show single violation type.
     */
    public function show(Request $request, ViolationType $violation_type): ViolationTypeResource|JsonResponse
    {
        $user = $request->user();
        if ($user->institution_id !== $violation_type->institution_id && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return new ViolationTypeResource($violation_type);
    }

    /**
     * Update violation type.
     */
    public function update(UpdateViolationTypeRequest $request, ViolationType $violation_type): ViolationTypeResource|JsonResponse
    {
        try {
            $user = $request->user();
            if ($user->institution_id !== $violation_type->institution_id && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $violation_type->update($request->validated());
            return new ViolationTypeResource($violation_type->fresh());
        } catch (\Exception $e) {
            Log::error('ViolationType update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui jenis pelanggaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete violation type.
     */
    public function destroy(Request $request, ViolationType $violation_type): JsonResponse
    {
        $user = $request->user();
        if ($user->institution_id !== $violation_type->institution_id && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($violation_type->violations()->exists()) {
            return response()->json([
                'message' => 'Jenis pelanggaran tidak dapat dihapus karena sudah digunakan pada catatan pelanggaran.',
            ], 422);
        }

        $violation_type->delete();
        return response()->json(['message' => 'Jenis pelanggaran berhasil dihapus.']);
    }
}

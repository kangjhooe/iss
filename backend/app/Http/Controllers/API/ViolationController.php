<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreViolationRequest;
use App\Http\Requests\UpdateViolationRequest;
use App\Http\Resources\ViolationResource;
use App\Models\Institution;
use App\Models\Violation;
use App\Services\ViolationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ViolationController extends Controller
{
    public function __construct(
        protected ViolationService $violationService
    ) {}

    /**
     * List violations for current institution.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['student_id', 'violation_type_id', 'status', 'date_from', 'date_to', 'search', 'academic_year_id', 'semester_id']);
            $institution = Institution::find($institutionId);
            if ($institution) {
                if (!isset($filters['academic_year_id']) && $institution->active_academic_year_id) {
                    $filters['academic_year_id'] = $institution->active_academic_year_id;
                }
                if (!isset($filters['semester_id']) && $institution->active_semester_id) {
                    $filters['semester_id'] = $institution->active_semester_id;
                }
            }
            $perPage = min($request->get('per_page', 15), 100);
            $violations = $this->violationService->listForInstitution($institutionId, $filters, $perPage);

            return ViolationResource::collection($violations);
        } catch (\Exception $e) {
            Log::error('Violation index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data pelanggaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a new violation.
     */
    public function store(StoreViolationRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $violation = $this->violationService->create(
                $institutionId,
                $request->validated(),
                $user->id
            );

            return (new ViolationResource($violation))
                ->response()
                ->setStatusCode(201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa atau jenis pelanggaran tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Violation store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencatat pelanggaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show single violation.
     */
    public function show(Request $request, Violation $violation): ViolationResource|JsonResponse
    {
        $user = $request->user();
        if ($user->institution_id !== $violation->institution_id && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $violation->load(['student.class:id,name', 'violationType', 'reporter']);
        return new ViolationResource($violation);
    }

    /**
     * Update violation.
     */
    public function update(UpdateViolationRequest $request, Violation $violation): ViolationResource|JsonResponse
    {
        try {
            $user = $request->user();
            if ($user->institution_id !== $violation->institution_id && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $violation = $this->violationService->update($violation, $request->validated());
            return new ViolationResource($violation);
        } catch (\Exception $e) {
            Log::error('Violation update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui pelanggaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete violation.
     */
    public function destroy(Request $request, Violation $violation): JsonResponse
    {
        $user = $request->user();
        if ($user->institution_id !== $violation->institution_id && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $violation->delete();
        return response()->json(['message' => 'Pelanggaran berhasil dihapus.']);
    }

    /**
     * List violations by student.
     */
    public function byStudent(Request $request, int $studentId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $violations = $this->violationService->listByStudent($studentId, $institutionId);
            return ViolationResource::collection($violations);
        } catch (\Exception $e) {
            Log::error('Violation byStudent failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil riwayat pelanggaran siswa.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

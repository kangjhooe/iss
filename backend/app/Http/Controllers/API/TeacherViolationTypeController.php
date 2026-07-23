<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherViolationTypeRequest;
use App\Http\Requests\UpdateTeacherViolationTypeRequest;
use App\Http\Resources\TeacherViolationTypeResource;
use App\Models\TeacherViolationType;
use App\Services\TeacherPointService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class TeacherViolationTypeController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected TeacherPointService $pointService
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $this->pointService->ensureDefaultViolationTypes($institutionId);

            $activeOnly = filter_var($request->get('active_only', true), FILTER_VALIDATE_BOOLEAN);
            $query = TeacherViolationType::forInstitution($institutionId)
                ->orderBy('sort_order')
                ->orderBy('name');

            if ($activeOnly) {
                $query->active();
            }
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            return TeacherViolationTypeResource::collection($query->get());
        } catch (\Exception $e) {
            Log::error('TeacherViolationType index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil jenis pelanggaran guru.'], 500);
        }
    }

    public function store(StoreTeacherViolationTypeRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['sort_order'] = $data['sort_order'] ?? 0;

            $type = TeacherViolationType::create($data);

            return (new TeacherViolationTypeResource($type))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('TeacherViolationType store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menambahkan jenis pelanggaran.'], 500);
        }
    }

    public function show(Request $request, TeacherViolationType $teacher_violation_type): TeacherViolationTypeResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $teacher_violation_type->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new TeacherViolationTypeResource($teacher_violation_type);
    }

    public function update(UpdateTeacherViolationTypeRequest $request, TeacherViolationType $teacher_violation_type): TeacherViolationTypeResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $teacher_violation_type->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacher_violation_type->update($request->validated());

        return new TeacherViolationTypeResource($teacher_violation_type->fresh());
    }

    public function destroy(Request $request, TeacherViolationType $teacher_violation_type): JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $teacher_violation_type->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($teacher_violation_type->violations()->exists()) {
            return response()->json(['message' => 'Jenis pelanggaran tidak dapat dihapus karena sudah digunakan.'], 422);
        }

        $teacher_violation_type->delete();

        return response()->json(['message' => 'Jenis pelanggaran berhasil dihapus.']);
    }
}

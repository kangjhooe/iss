<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherAchievementTypeRequest;
use App\Http\Requests\UpdateTeacherAchievementTypeRequest;
use App\Http\Resources\TeacherAchievementTypeResource;
use App\Models\TeacherAchievementType;
use App\Services\TeacherPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class TeacherAchievementTypeController extends Controller
{
    public function __construct(
        protected TeacherPointService $pointService
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $request->user()->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $this->pointService->ensureDefaultTypes($institutionId);

            $activeOnly = filter_var($request->get('active_only', true), FILTER_VALIDATE_BOOLEAN);
            $query = TeacherAchievementType::forInstitution($institutionId)
                ->orderBy('sort_order')
                ->orderBy('name');

            if ($activeOnly) {
                $query->active();
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            return TeacherAchievementTypeResource::collection($query->get());
        } catch (\Exception $e) {
            Log::error('TeacherAchievementType index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil jenis prestasi guru.'], 500);
        }
    }

    public function store(StoreTeacherAchievementTypeRequest $request): JsonResponse
    {
        try {
            $institutionId = $request->user()->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['sort_order'] = $data['sort_order'] ?? 0;
            $data['level_multipliers'] = $data['level_multipliers'] ?? TeacherAchievementType::DEFAULT_MULTIPLIERS;

            $type = TeacherAchievementType::create($data);

            return (new TeacherAchievementTypeResource($type))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('TeacherAchievementType store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menambahkan jenis prestasi guru.'], 500);
        }
    }

    public function show(Request $request, TeacherAchievementType $teacher_achievement_type): TeacherAchievementTypeResource|JsonResponse
    {
        if ($request->user()->institution_id !== $teacher_achievement_type->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new TeacherAchievementTypeResource($teacher_achievement_type);
    }

    public function update(UpdateTeacherAchievementTypeRequest $request, TeacherAchievementType $teacher_achievement_type): TeacherAchievementTypeResource|JsonResponse
    {
        if ($request->user()->institution_id !== $teacher_achievement_type->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacher_achievement_type->update($request->validated());

        return new TeacherAchievementTypeResource($teacher_achievement_type->fresh());
    }

    public function destroy(Request $request, TeacherAchievementType $teacher_achievement_type): JsonResponse
    {
        if ($request->user()->institution_id !== $teacher_achievement_type->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($teacher_achievement_type->achievements()->exists()) {
            return response()->json(['message' => 'Jenis prestasi tidak dapat dihapus karena sudah digunakan.'], 422);
        }

        $teacher_achievement_type->delete();

        return response()->json(['message' => 'Jenis prestasi berhasil dihapus.']);
    }
}

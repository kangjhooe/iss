<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherPointRewardRequest;
use App\Http\Requests\UpdateTeacherPointRewardRequest;
use App\Http\Resources\TeacherPointRewardResource;
use App\Models\TeacherPointReward;
use App\Services\TeacherPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class TeacherPointRewardController extends Controller
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

            $this->pointService->ensureDefaultRewards($institutionId);

            $activeOnly = filter_var($request->get('active_only', true), FILTER_VALIDATE_BOOLEAN);
            $query = TeacherPointReward::forInstitution($institutionId)
                ->orderBy('sort_order')
                ->orderBy('point_min');

            if ($activeOnly) {
                $query->active();
            }

            return TeacherPointRewardResource::collection($query->get());
        } catch (\Exception $e) {
            Log::error('TeacherPointReward index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil aturan reward.'], 500);
        }
    }

    public function store(StoreTeacherPointRewardRequest $request): JsonResponse
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

            $reward = TeacherPointReward::create($data);

            return (new TeacherPointRewardResource($reward))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('TeacherPointReward store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menambahkan aturan reward.'], 500);
        }
    }

    public function show(Request $request, TeacherPointReward $teacher_point_reward): TeacherPointRewardResource|JsonResponse
    {
        if ($request->user()->institution_id !== $teacher_point_reward->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new TeacherPointRewardResource($teacher_point_reward);
    }

    public function update(UpdateTeacherPointRewardRequest $request, TeacherPointReward $teacher_point_reward): TeacherPointRewardResource|JsonResponse
    {
        if ($request->user()->institution_id !== $teacher_point_reward->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacher_point_reward->update($request->validated());

        return new TeacherPointRewardResource($teacher_point_reward->fresh());
    }

    public function destroy(Request $request, TeacherPointReward $teacher_point_reward): JsonResponse
    {
        if ($request->user()->institution_id !== $teacher_point_reward->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($teacher_point_reward->rewardLogs()->exists()) {
            return response()->json(['message' => 'Aturan reward tidak dapat dihapus karena sudah digunakan.'], 422);
        }

        $teacher_point_reward->delete();

        return response()->json(['message' => 'Aturan reward berhasil dihapus.']);
    }
}

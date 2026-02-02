<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAchievementTypeRequest;
use App\Http\Requests\UpdateAchievementTypeRequest;
use App\Http\Resources\AchievementTypeResource;
use App\Models\AchievementType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class AchievementTypeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $request->user()->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $activeOnly = filter_var($request->get('active_only', true), FILTER_VALIDATE_BOOLEAN);
            $query = AchievementType::forInstitution($institutionId)->orderBy('name');
            if ($activeOnly) {
                $query->active();
            }
            $items = $query->get();
            return AchievementTypeResource::collection($items);
        } catch (\Exception $e) {
            Log::error('AchievementType index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil jenis prestasi.'], 500);
        }
    }

    public function store(StoreAchievementTypeRequest $request): JsonResponse
    {
        try {
            $institutionId = $request->user()->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $type = AchievementType::create($data);
            return (new AchievementTypeResource($type))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('AchievementType store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menambahkan jenis prestasi.'], 500);
        }
    }

    public function show(Request $request, AchievementType $achievement_type): AchievementTypeResource|JsonResponse
    {
        if ($request->user()->institution_id !== $achievement_type->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return new AchievementTypeResource($achievement_type);
    }

    public function update(UpdateAchievementTypeRequest $request, AchievementType $achievement_type): AchievementTypeResource|JsonResponse
    {
        if ($request->user()->institution_id !== $achievement_type->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $achievement_type->update($request->validated());
        return new AchievementTypeResource($achievement_type->fresh());
    }

    public function destroy(Request $request, AchievementType $achievement_type): JsonResponse
    {
        if ($request->user()->institution_id !== $achievement_type->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($achievement_type->achievements()->exists()) {
            return response()->json(['message' => 'Jenis prestasi tidak dapat dihapus karena sudah digunakan.'], 422);
        }
        $achievement_type->delete();
        return response()->json(['message' => 'Jenis prestasi berhasil dihapus.']);
    }
}

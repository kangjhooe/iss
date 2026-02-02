<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePointThresholdRequest;
use App\Http\Requests\UpdatePointThresholdRequest;
use App\Http\Resources\PointThresholdResource;
use App\Models\PointThreshold;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class PointThresholdController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $request->user()->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $activeOnly = filter_var($request->get('active_only', true), FILTER_VALIDATE_BOOLEAN);
            $query = PointThreshold::forInstitution($institutionId)->orderBy('sort_order')->orderBy('point_min');
            if ($activeOnly) {
                $query->active();
            }
            $items = $query->get();
            return PointThresholdResource::collection($items);
        } catch (\Exception $e) {
            Log::error('PointThreshold index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil aturan tindakan.'], 500);
        }
    }

    public function store(StorePointThresholdRequest $request): JsonResponse
    {
        try {
            $institutionId = $request->user()->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $data['sort_order'] = $data['sort_order'] ?? 0;
            $threshold = PointThreshold::create($data);
            return (new PointThresholdResource($threshold))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('PointThreshold store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menambahkan aturan tindakan.'], 500);
        }
    }

    public function show(Request $request, PointThreshold $point_threshold): PointThresholdResource|JsonResponse
    {
        if ($request->user()->institution_id !== $point_threshold->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return new PointThresholdResource($point_threshold);
    }

    public function update(UpdatePointThresholdRequest $request, PointThreshold $point_threshold): PointThresholdResource|JsonResponse
    {
        if ($request->user()->institution_id !== $point_threshold->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $point_threshold->update($request->validated());
        return new PointThresholdResource($point_threshold->fresh());
    }

    public function destroy(Request $request, PointThreshold $point_threshold): JsonResponse
    {
        if ($request->user()->institution_id !== $point_threshold->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $point_threshold->delete();
        return response()->json(['message' => 'Aturan tindakan berhasil dihapus.']);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\DashboardChartsService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardChartsController extends Controller
{
    public function __construct(protected DashboardChartsService $chartsService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->isStudent() || $user->isParent()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $institutionId = InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        try {
            return response()->json([
                'data' => $this->chartsService->build((int) $institutionId),
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard charts failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memuat data chart dashboard.'], 500);
        }
    }
}

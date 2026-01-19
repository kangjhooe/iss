<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CorrespondenceStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CorrespondenceStatisticsController extends Controller
{
    public function __construct(
        private CorrespondenceStatisticsService $service
    ) {}

    /**
     * Get correspondence statistics for dashboard.
     */
    public function index(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            $year = $request->get('year');
            $month = $request->get('month');

            $statistics = $this->service->getStatistics($institutionId, $year, $month);

            return response()->json([
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get correspondence statistics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil statistik',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CorrespondenceStatisticsService;
use App\Support\InstitutionContext;
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
            $user = $request->user();
            $institutionId = $user->isAdminOrSuperAdmin()
                ? ($request->filled('institution_id') ? (int) $request->get('institution_id') : null)
                : InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));

            $year = $request->get('year');
            $month = $request->get('month');
            $academicYearId = $request->filled('academic_year_id')
                ? (int) $request->get('academic_year_id')
                : null;

            $statistics = $this->service->getStatistics($institutionId, $year, $month, $academicYearId);

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

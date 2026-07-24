<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\SuperAdminAdoptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SuperAdminAdoptionController extends Controller
{
    public function __construct(
        protected SuperAdminAdoptionService $adoptionService
    ) {}

    private function ensureSuperAdmin(Request $request): void
    {
        if (!$request->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Module adoption, usage, and churn monitoring across institutions.
     */
    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        try {
            $inactiveDays = max(1, min((int) $request->get('inactive_days', 30), 365));
            $usageDays = max(7, min((int) $request->get('usage_days', 30), 90));

            return response()->json([
                'data' => $this->adoptionService->build($inactiveDays, $usageDays),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load adoption monitoring', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memuat monitoring adopsi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

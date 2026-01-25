<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\InventoryReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InventoryReportController extends Controller
{
    public function __construct(
        private InventoryReportService $service
    ) {}

    /**
     * Get inventory statistics.
     */
    public function statistics(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            $year = $request->get('year');
            $statistics = $this->service->getStatistics($institutionId, $year);

            return response()->json(['data' => $statistics]);
        } catch (\Exception $e) {
            Log::error('Failed to get statistics', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Get items by category.
     */
    public function byCategory(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            $categoryId = $request->get('category_id');
            $data = $this->service->getItemsByCategory($institutionId, $categoryId);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to get items by category', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Get items by location.
     */
    public function byLocation(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            $data = $this->service->getItemsByLocation($institutionId);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to get items by location', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Get damaged/missing items.
     */
    public function damagedMissing(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            $data = $this->service->getDamagedMissingItems($institutionId);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to get damaged/missing items', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Get loaned items.
     */
    public function loaned(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            $data = $this->service->getLoanedItems($institutionId);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to get loaned items', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Get asset value report.
     */
    public function assetValue(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            $data = $this->service->getAssetValueReport($institutionId);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to get asset value report', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Get maintenance report.
     */
    public function maintenance(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            $year = $request->get('year');
            $data = $this->service->getMaintenanceReport($institutionId, $year);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to get maintenance report', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Get transaction report.
     */
    public function transactions(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $data = $this->service->getTransactionReport($institutionId, $dateFrom, $dateTo);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to get transaction report', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }
}

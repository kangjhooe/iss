<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InventoryItem;
use App\Services\InventoryReportService;
use App\Support\InstitutionContext;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InventoryReportController extends Controller
{
    public function __construct(
        private InventoryReportService $service
    ) {}

    protected function resolveInstitutionId(Request $request): ?int
    {
        $user = $request->user();
        if (!$user->isAdminOrSuperAdmin()) {
            return InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));
        }
        if ($request->filled('institution_id')) {
            return (int) $request->institution_id;
        }

        return InstitutionContext::resolveForUser($user, $request, null);
    }

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

    /**
     * Export laporan inventaris (PDF) — statistik, aset, rusak/hilang, pinjaman, daftar barang.
     */
    public function exportPdf(Request $request)
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 403);
            }

            $institution = Institution::find($institutionId);
            if (!$institution) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 404);
            }

            $statistics = $this->service->getStatistics($institutionId);
            $damagedMissing = $this->service->getDamagedMissingItems($institutionId);
            $loaned = $this->service->getLoanedItems($institutionId);
            $assetValue = $this->service->getAssetValueReport($institutionId);

            $itemsQuery = InventoryItem::with(['category:id,name', 'room:id,name', 'building:id,name'])
                ->where('institution_id', $institutionId)
                ->orderBy('name');

            $totalItems = (clone $itemsQuery)->count();
            $items = $itemsQuery->limit(2000)->get();

            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');

            $pdf = DomPDF::loadView('inventory.report', [
                'institution' => $institution,
                'statistics' => $statistics,
                'damaged_missing' => $damagedMissing,
                'loaned' => $loaned,
                'asset_value' => $assetValue,
                'items' => $items,
                'items_truncated' => $totalItems > $items->count(),
                'printed_at' => $printedAt,
                'printed_by' => $user->name,
            ])->setPaper('a4', 'landscape');

            $filename = 'Laporan_Inventaris_' . now()->format('Ymd_His') . '.pdf';

            return $pdf->stream($filename, ['Attachment' => false]);
        } catch (\Exception $e) {
            Log::error('Failed to export inventory PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Gagal mengekspor laporan inventaris',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

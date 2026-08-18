<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Institution;
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

    protected function institutionIdForJson(Request $request): ?int
    {
        if (!$request->user()->isAdminOrSuperAdmin()) {
            return $request->user()->institution_id;
        }
        if ($request->filled('institution_id')) {
            return (int) $request->institution_id;
        }

        return null;
    }

    protected function filtersFromRequest(Request $request): array
    {
        return $this->service->normalizeFilters($request->all());
    }

    /**
     * Get inventory statistics.
     */
    public function statistics(Request $request)
    {
        try {
            $filters = $this->filtersFromRequest($request);
            $statistics = $this->service->getStatistics(
                $this->institutionIdForJson($request),
                $request->get('year'),
                $filters
            );

            return response()->json(['data' => $statistics]);
        } catch (\Exception $e) {
            Log::error('Failed to get statistics', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Get stock / daftar barang report.
     */
    public function stock(Request $request)
    {
        try {
            $filters = $this->filtersFromRequest($request);
            $data = $this->service->getStockReport($this->institutionIdForJson($request), $filters);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to get stock report', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Get items by category.
     */
    public function byCategory(Request $request)
    {
        try {
            $filters = $this->filtersFromRequest($request);
            $data = $this->service->getItemsByCategory(
                $this->institutionIdForJson($request),
                $request->get('category_id'),
                $filters
            );

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
            $filters = $this->filtersFromRequest($request);
            $data = $this->service->getItemsByLocation($this->institutionIdForJson($request), $filters);

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
            $filters = $this->filtersFromRequest($request);
            $data = $this->service->getDamagedMissingItems($this->institutionIdForJson($request), $filters);

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
            $filters = $this->filtersFromRequest($request);
            $data = $this->service->getLoanedItems($this->institutionIdForJson($request), $filters);

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
            $filters = $this->filtersFromRequest($request);
            $data = $this->service->getAssetValueReport($this->institutionIdForJson($request), $filters);

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
            $filters = $this->filtersFromRequest($request);
            $data = $this->service->getMaintenanceReport(
                $this->institutionIdForJson($request),
                $request->get('year'),
                $filters
            );

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
            $filters = $this->filtersFromRequest($request);
            $data = $this->service->getTransactionReport(
                $this->institutionIdForJson($request),
                $request->get('date_from'),
                $request->get('date_to'),
                $filters
            );

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to get transaction report', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Export laporan inventaris (PDF) — per jenis laporan + filter.
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

            $filters = $this->filtersFromRequest($request);
            $reportType = $this->service->resolveReportType($request->get('type'));
            $filterLegend = $this->service->buildFilterLegend($filters);

            $statistics = null;
            $stock = null;
            $damagedMissing = null;
            $loaned = null;
            $assetValue = null;
            $transactions = null;
            $maintenance = null;

            if (in_array($reportType, ['summary', 'stock'], true)) {
                $statistics = $this->service->getStatistics($institutionId, $filters['year'] ?? null, $filters);
            }
            if (in_array($reportType, ['summary', 'stock'], true)) {
                $stock = $this->service->getStockReport(
                    $institutionId,
                    $filters,
                    $reportType === 'stock' ? 5000 : 2000
                );
            }
            if (in_array($reportType, ['summary', 'damaged'], true)) {
                $damagedMissing = $this->service->getDamagedMissingItems($institutionId, $filters);
            }
            if (in_array($reportType, ['summary', 'loaned'], true)) {
                $loaned = $this->service->getLoanedItems($institutionId, $filters);
            }
            if (in_array($reportType, ['summary', 'asset'], true)) {
                $assetValue = $this->service->getAssetValueReport($institutionId, $filters);
            }
            if ($reportType === 'transactions') {
                $transactions = $this->service->getTransactionReport($institutionId, null, null, $filters);
            }
            if ($reportType === 'maintenance') {
                $maintenance = $this->service->getMaintenanceReport($institutionId, null, $filters);
            }

            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');

            $pdf = DomPDF::loadView('inventory.report', [
                'institution' => $institution,
                'report_type' => $reportType,
                'report_title' => $this->service->reportTypeLabel($reportType),
                'filter_legend' => $filterLegend,
                'statistics' => $statistics,
                'stock' => $stock,
                'damaged_missing' => $damagedMissing,
                'loaned' => $loaned,
                'asset_value' => $assetValue,
                'transactions' => $transactions,
                'maintenance' => $maintenance,
                'printed_at' => $printedAt,
                'printed_by' => $user->name,
            ])->setPaper('a4', 'landscape');

            try {
                $pdf->render();
                $canvas = $pdf->getDomPDF()->getCanvas();
                $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans');
                // Landscape A4: ~842 x 595 pt
                $canvas->page_text(720, 575, 'Hal. {PAGE_NUM}/{PAGE_COUNT}', $font, 7, [0.35, 0.35, 0.35]);
            } catch (\Throwable $e) {
                // Page numbers are optional; keep PDF export working if canvas API differs.
                Log::warning('Inventory PDF page number skipped', ['error' => $e->getMessage()]);
            }

            $filename = 'Laporan_Inventaris_' . $reportType . '_' . now()->format('Ymd_His') . '.pdf';

            return $pdf->stream($filename);
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

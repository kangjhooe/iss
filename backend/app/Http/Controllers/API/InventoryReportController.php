<?php

namespace App\Http\Controllers\API;

use App\Exports\InventoryReportExport;
use App\Http\Controllers\API\Concerns\ResolvesActiveInstitution;
use App\Http\Controllers\Controller;
use App\Support\StructuralPositionResolver;
use App\Models\InventoryAsset;
use App\Models\InventoryItem;
use App\Services\InventoryReportService;
use App\Support\InstitutionContext;
use App\Support\InventoryAccess;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryReportController extends Controller
{
    use ResolvesActiveInstitution;

    public function __construct(
        private InventoryReportService $service
    ) {}

    protected function institutionIdForJson(Request $request): ?int
    {
        return $this->resolveInstitutionId($request);
    }

    protected function filtersFromRequest(Request $request): array
    {
        $filters = $this->service->normalizeFilters($request->all());
        $institutionId = $this->institutionIdForJson($request);
        if ($institutionId) {
            return $this->inventoryFiltersWithRoomScope($request, $filters, $institutionId);
        }

        return $filters;
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
        if ($denied = $this->denyUnlessInventoryReports($request)) {
            return $denied;
        }

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
        if ($denied = $this->denyUnlessInventoryReports($request)) {
            return $denied;
        }

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
        if ($denied = $this->denyUnlessInventoryReports($request)) {
            return $denied;
        }

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
        if ($denied = $this->denyUnlessInventoryReports($request)) {
            return $denied;
        }

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
        if ($denied = $this->denyUnlessInventoryReports($request)) {
            return $denied;
        }

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
        if ($denied = $this->denyUnlessInventoryReports($request)) {
            return $denied;
        }

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
        if ($denied = $this->denyUnlessInventoryReports($request)) {
            return $denied;
        }

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
        if ($denied = $this->denyUnlessInventoryReports($request)) {
            return $denied;
        }

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
     * Get individual asset movement report.
     */
    public function assetMovements(Request $request)
    {
        if ($denied = $this->denyUnlessInventoryReports($request)) {
            return $denied;
        }

        try {
            $filters = $this->filtersFromRequest($request);
            $data = $this->service->getAssetMovementReport($this->institutionIdForJson($request), $filters);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to get asset movement report', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Get disposed items report.
     */
    public function disposed(Request $request)
    {
        if ($denied = $this->denyUnlessInventoryReports($request)) {
            return $denied;
        }

        try {
            $filters = $this->filtersFromRequest($request);
            $data = $this->service->getDisposedItems($this->institutionIdForJson($request), $filters);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to get disposed items report', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Export laporan inventaris (PDF) — per jenis laporan + filter.
     */
    public function exportPdf(Request $request)
    {
        if ($denied = $this->denyUnlessInventoryReports($request)) {
            return $denied;
        }

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

            $payload = $this->collectReportPayload($institutionId, $reportType, $filters);

            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');
            $asOfDate = StructuralPositionResolver::reportAsOfDate($filters);

            $pdf = DomPDF::loadView('inventory.report', [
                'institution' => $institution,
                'report_type' => $reportType,
                'report_title' => $this->service->reportTypeLabel($reportType),
                'filter_legend' => $filterLegend,
                'statistics' => $payload['statistics'],
                'stock' => $payload['stock'],
                'damaged_missing' => $payload['damaged_missing'],
                'loaned' => $payload['loaned'],
                'asset_value' => $payload['asset_value'],
                'transactions' => $payload['transactions'],
                'maintenance' => $payload['maintenance'],
                'by_location' => $payload['by_location'],
                'by_category' => $payload['by_category'],
                'disposed' => $payload['disposed'],
                'asset_movements' => $payload['asset_movements'],
                'printed_at' => $printedAt,
                'printed_by' => $user->name,
                'as_of_date' => $asOfDate,
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

    /**
     * Export laporan inventaris ke Excel (.xlsx).
     */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        if ($denied = $this->denyUnlessInventoryReports($request)) {
            abort(403, $denied->getData(true)['message'] ?? 'Forbidden');
        }

        $institutionId = $this->resolveInstitutionId($request);
        if (! $institutionId) {
            abort(403, 'Institusi tidak ditemukan');
        }

        $filters = $this->filtersFromRequest($request);
        $reportType = $this->service->resolveReportType($request->get('type'));
        $payload = $this->collectReportPayload($institutionId, $reportType, $filters);

        $built = InventoryReportExport::buildRows($reportType, [
            'statistics' => $payload['statistics'],
            'stock' => $payload['stock'],
            'by_location' => $payload['by_location'],
            'by_category' => $payload['by_category'],
            'asset_value' => $payload['asset_value'],
            'damaged_missing' => $payload['damaged_missing'],
            'loaned' => $payload['loaned'],
            'transactions' => $payload['transactions'],
            'asset_movements' => $payload['asset_movements'],
            'maintenance' => $payload['maintenance'],
            'disposed' => $payload['disposed'],
        ]);

        $filename = 'Laporan_Inventaris_' . $reportType . '_' . now()->format('Ymd_His') . '.xlsx';

        return app(ExcelManager::class)->download(
            new InventoryReportExport(
                $reportType,
                $this->service->reportTypeLabel($reportType),
                $built['headings'],
                $built['rows']
            ),
            $filename,
            ExcelManager::XLSX
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function collectReportPayload(int $institutionId, string $reportType, array $filters): array
    {
        $payload = [
            'statistics' => null,
            'stock' => null,
            'damaged_missing' => null,
            'loaned' => null,
            'asset_value' => null,
            'transactions' => null,
            'maintenance' => null,
            'by_location' => null,
            'by_category' => null,
            'disposed' => null,
            'asset_movements' => null,
        ];

        if (in_array($reportType, ['summary', 'stock'], true)) {
            $payload['statistics'] = $this->service->getStatistics($institutionId, $filters['year'] ?? null, $filters);
        }
        if (in_array($reportType, ['summary', 'stock'], true)) {
            $payload['stock'] = $this->service->getStockReport(
                $institutionId,
                $filters,
                $reportType === 'stock' ? 5000 : 2000
            );
        }
        if (in_array($reportType, ['summary', 'damaged'], true) || $reportType === 'damaged') {
            $payload['damaged_missing'] = $this->service->getDamagedMissingItems($institutionId, $filters);
        }
        if (in_array($reportType, ['summary', 'loaned'], true) || $reportType === 'loaned') {
            $payload['loaned'] = $this->service->getLoanedItems($institutionId, $filters);
        }
        if (in_array($reportType, ['summary', 'asset'], true) || $reportType === 'asset') {
            $payload['asset_value'] = $this->service->getAssetValueReport($institutionId, $filters);
        }
        if ($reportType === 'transactions') {
            $payload['transactions'] = $this->service->getTransactionReport($institutionId, null, null, $filters);
        }
        if ($reportType === 'maintenance') {
            $payload['maintenance'] = $this->service->getMaintenanceReport($institutionId, null, $filters);
        }
        if ($reportType === 'location') {
            $payload['by_location'] = $this->service->getItemsByLocation($institutionId, $filters);
        }
        if ($reportType === 'category') {
            $payload['by_category'] = $this->service->getItemsByCategory(
                $institutionId,
                $filters['category_id'] ?? null,
                $filters
            );
        }
        if ($reportType === 'disposal') {
            $payload['disposed'] = $this->service->getDisposedItems($institutionId, $filters);
        }
        if ($reportType === 'asset_movements') {
            $payload['asset_movements'] = $this->service->getAssetMovementReport($institutionId, $filters);
        }

        return $payload;
    }

    /**
     * Export Kartu Inventaris Barang (KIB) for a single item.
     */
    public function exportKib(Request $request, InventoryItem $item)
    {
        try {
            if (! $this->userCanAccessInventoryItem($request, $item)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId || (int) $item->institution_id !== (int) $institutionId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $institution = Institution::find($institutionId);
            if (!$institution) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 404);
            }

            $item->load([
                'category', 'room', 'building', 'institution',
                'responsibleEmployee', 'creator',
            ]);

            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');
            $asOfDate = $item->purchase_date ?? now();
            $signatureDate = $asOfDate->locale('id')->translatedFormat('d F Y');

            $pdf = DomPDF::loadView('inventory.kib', [
                'institution' => $institution,
                'item' => $item,
                'printed_at' => $printedAt,
                'printed_by' => $user->name,
                'signature_date' => $signatureDate,
                'as_of_date' => $asOfDate,
            ])->setPaper('a4', 'portrait');

            $safeCode = preg_replace('/[^a-zA-Z0-9_-]/', '_', $item->code ?? 'barang');
            $filename = 'KIB_' . $safeCode . '_' . now()->format('Ymd') . '.pdf';

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            Log::error('Failed to export KIB', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal mencetak Kartu Inventaris Barang',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export KIB untuk satu unit aset individual.
     */
    public function exportAssetKib(Request $request, InventoryAsset $asset)
    {
        try {
            if (! $this->userCanAccessInventoryAsset($request, $asset)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId || (int) $asset->institution_id !== (int) $institutionId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $institution = Institution::find($institutionId);
            if (! $institution) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 404);
            }

            $asset->load([
                'item.category', 'item.institution', 'room', 'building',
                'responsibleEmployee', 'item.responsibleEmployee',
            ]);

            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');
            $asOfDate = $asset->item?->purchase_date ?? now();
            $signatureDate = $asOfDate->locale('id')->translatedFormat('d F Y');

            $pdf = DomPDF::loadView('inventory.kib_asset', [
                'institution' => $institution,
                'item' => $asset->item,
                'asset' => $asset,
                'printed_at' => $printedAt,
                'printed_by' => $user->name,
                'signature_date' => $signatureDate,
                'as_of_date' => $asOfDate,
            ])->setPaper('a4', 'portrait');

            $safeNumber = preg_replace('/[^a-zA-Z0-9_-]/', '_', $asset->asset_number ?? 'aset');
            $filename = 'KIB_' . $safeNumber . '_' . now()->format('Ymd') . '.pdf';

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            Log::error('Failed to export asset KIB', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal mencetak KIB aset',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

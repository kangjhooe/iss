<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CorrespondenceExportService;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CorrespondenceExportController extends Controller
{
    public function __construct(
        private CorrespondenceExportService $service
    ) {}

    /**
     * Export correspondence to Excel.
     */
    public function exportExcel(Request $request)
    {
        try {
            $filters = $request->only([
                'type', 'status', 'priority', 'category_id', 'letter_type_code', 'search',
                'date_from', 'date_to'
            ]);

            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            $filePath = $this->service->exportToExcel($filters, $institutionId);

            return response()->json([
                'message' => 'Export berhasil',
                'download_url' => Storage::disk('public')->url($filePath),
                'file_path' => $filePath,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to export correspondence to Excel', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengekspor data',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Download Excel file.
     * Mencegah path traversal: hanya path di bawah exports/ yang diizinkan.
     */
    public function downloadExcel(Request $request, string $filePath)
    {
        try {
            $filePath = $this->resolveSafeExportPath($filePath);
            if ($filePath === null) {
                return response()->json(['message' => 'File tidak ditemukan'], 404);
            }
            if (!Storage::disk('public')->exists($filePath)) {
                return response()->json(['message' => 'File tidak ditemukan'], 404);
            }
            return Storage::disk('public')->download($filePath);
        } catch (\Exception $e) {
            Log::error('Failed to download Excel file', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
            ]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengunduh file',
            ], 500);
        }
    }

    /**
     * Export correspondence to PDF report.
     */
    public function exportPdf(Request $request)
    {
        try {
            $filters = $request->only([
                'type', 'status', 'priority', 'category_id', 'letter_type_code', 'search',
                'date_from', 'date_to'
            ]);

            $institutionId = null;
            $institution = null;
            
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
                $institution = Institution::find($institutionId);
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
                $institution = Institution::find($institutionId);
            }

            $filePath = $this->service->exportToPdf($filters, $institutionId, $institution);

            return response()->json([
                'message' => 'Export PDF berhasil',
                'download_url' => Storage::disk('public')->url($filePath),
                'file_path' => $filePath,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to export correspondence to PDF', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengekspor PDF',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Download PDF file.
     * Mencegah path traversal: hanya path di bawah exports/ yang diizinkan.
     */
    public function downloadPdf(Request $request, string $filePath)
    {
        try {
            $filePath = $this->resolveSafeExportPath($filePath);
            if ($filePath === null) {
                return response()->json(['message' => 'File tidak ditemukan'], 404);
            }
            if (!Storage::disk('public')->exists($filePath)) {
                return response()->json(['message' => 'File tidak ditemukan'], 404);
            }
            return Storage::disk('public')->download($filePath);
        } catch (\Exception $e) {
            Log::error('Failed to download PDF file', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
            ]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengunduh file',
            ], 500);
        }
    }

    /**
     * Resolve and validate export file path to prevent path traversal.
     * Only paths under exports/ are allowed. Returns null if invalid.
     */
    private function resolveSafeExportPath(string $filePath): ?string
    {
        $filePath = trim($filePath);
        if ($filePath === '' || str_contains($filePath, '..')) {
            return null;
        }
        $filePath = str_replace('\\', '/', $filePath);
        if (str_starts_with($filePath, '/')) {
            $filePath = ltrim($filePath, '/');
        }
        if (!str_starts_with($filePath, 'exports/')) {
            return null;
        }
        return $filePath;
    }
}

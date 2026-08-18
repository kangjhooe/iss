<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Services\UksReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UksReportController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected UksReportService $uksReportService
    ) {}

    public function summary(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $this->resolveFilters($request, $institutionId);
            $data = $this->uksReportService->getSummary($institutionId, $filters);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('UKS report summary failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil laporan UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function visits(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $this->resolveFilters($request, $institutionId);
            $filters['apply_year_filter'] = true;
            $data = $this->uksReportService->getVisitDetail($institutionId, $filters);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('UKS report visits failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil detail kunjungan UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $this->resolveFilters($request, $institutionId);
            $rows = $this->uksReportService->byClass($institutionId, $filters);
            $filename = 'laporan-uks-rekap-kelas-'.date('Y-m-d-His').'.csv';

            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($out, ['Kelas', 'Jumlah Kunjungan', 'Jumlah Rujukan']);
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row['class_name'],
                        $row['visit_count'],
                        $row['referral_count'],
                    ]);
                }
                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            Log::error('UKS report export failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengekspor rekap UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function exportVisits(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $this->resolveFilters($request, $institutionId);
            $filters['apply_year_filter'] = true;
            $data = $this->uksReportService->getVisitDetail($institutionId, $filters);
            $filename = 'laporan-uks-detail-'.date('Y-m-d-His').'.csv';

            return response()->streamDownload(function () use ($data) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($out, [
                    'Tanggal', 'NIS', 'NISN', 'Nama Siswa', 'Kelas', 'Jenis', 'Status',
                    'Keluhan', 'Tindakan', 'Catatan', 'Petugas',
                ]);
                foreach ($data['items'] as $row) {
                    fputcsv($out, [
                        $row['visit_date'] ?? '',
                        $row['nis'] ?? '',
                        $row['nisn'] ?? '',
                        $row['student_name'] ?? '',
                        $row['class_name'] ?? '',
                        $row['visit_type'] ?? '',
                        $row['status'] ?? '',
                        $row['complaint'] ?? '',
                        $row['action_taken'] ?? '',
                        $row['notes'] ?? '',
                        $row['recorder_name'] ?? '',
                    ]);
                }
                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            Log::error('UKS report export visits failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengekspor detail UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    protected function resolveFilters(Request $request, int $institutionId): array
    {
        $filters = $request->only([
            'academic_year_id', 'semester_id', 'class_id', 'date_from', 'date_to',
            'year', 'month', 'uks_visit_type_id', 'status',
        ]);

        $institution = Institution::find($institutionId);
        if ($institution) {
            if (!array_key_exists('academic_year_id', $filters) || $filters['academic_year_id'] === null || $filters['academic_year_id'] === '') {
                // allow empty = all years when explicitly sent as empty string from UI
            }
        }

        if (empty($filters['year'])) {
            $filters['year'] = (int) now()->format('Y');
        }

        foreach (['academic_year_id', 'semester_id', 'class_id', 'uks_visit_type_id', 'month'] as $key) {
            if (isset($filters[$key]) && $filters[$key] === '') {
                unset($filters[$key]);
            }
        }

        return $filters;
    }
}

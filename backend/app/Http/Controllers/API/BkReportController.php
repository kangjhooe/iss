<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Services\BkReportService;
use App\Support\WaliKelasAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BkReportController extends Controller
{
    public function __construct(
        protected BkReportService $bkReportService
    ) {}

    /**
     * Ringkasan laporan BK (rekap per kelas, per bulan, top jenis pelanggaran).
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user?->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $this->resolveFilters($request, $institutionId, $user);
            if ($filters === null) {
                return response()->json(['data' => $this->emptySummaryPayload($request)]);
            }
            $data = $this->bkReportService->getSummary($institutionId, $filters);
            $data['scope'] = $this->scopeMeta($user);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('BK report summary failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil laporan BK.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Detail pelanggaran: daftar per siswa + rekap.
     */
    public function violationDetail(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user?->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $this->resolveFilters($request, $institutionId, $user);
            if ($filters === null) {
                return response()->json(['data' => [
                    'items' => [],
                    'achievements' => [],
                    'by_student' => [],
                    'total' => 0,
                    'achievements_total' => 0,
                    'truncated' => false,
                    'scope' => $this->scopeMeta($user),
                ]]);
            }
            $filters['apply_year_filter'] = true;
            $data = $this->bkReportService->getViolationDetail($institutionId, $filters);
            $data['scope'] = $this->scopeMeta($user);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('BK report violation detail failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil detail pelanggaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export CSV rekap per kelas.
     */
    public function export(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user?->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $this->resolveFilters($request, $institutionId, $user);
            if ($filters === null) {
                $rows = [];
            } else {
                $rows = $this->bkReportService->exportByClassRows($institutionId, $filters);
            }
            $filename = 'laporan-bk-per-kelas-'.now()->format('Y-m-d').'.csv';

            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($out, ['Kelas', 'Jumlah Pelanggaran', 'Jumlah Prestasi', 'Poin Prestasi', 'Jumlah Konseling', 'Total Kasus']);
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row['class_name'],
                        $row['violation_count'],
                        $row['achievement_count'] ?? 0,
                        $row['achievement_points'] ?? 0,
                        $row['counseling_count'],
                        $row['violation_count'] + $row['counseling_count'] + ($row['achievement_count'] ?? 0),
                    ]);
                }
                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            Log::error('BK report export failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengekspor laporan BK.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export CSV detail pelanggaran.
     */
    public function exportViolations(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user?->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $this->resolveFilters($request, $institutionId, $user);
            if ($filters === null) {
                $data = [
                    'items' => [],
                    'achievements' => [],
                    'by_student' => [],
                ];
            } else {
                $filters['apply_year_filter'] = true;
                $data = $this->bkReportService->getViolationDetail($institutionId, $filters);
            }
            $filename = 'laporan-bk-detail-skor-siswa-'.now()->format('Y-m-d').'.csv';

            return response()->streamDownload(function () use ($data) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($out, ['=== REKAP SKOR PER SISWA ===']);
                fputcsv($out, [
                    'NIS', 'NISN', 'Nama Siswa', 'Kelas',
                    'Jml Pelanggaran', 'Poin Pelanggaran',
                    'Jml Prestasi', 'Poin Prestasi', 'Skor',
                ]);
                foreach ($data['by_student'] as $row) {
                    fputcsv($out, [
                        $row['nis'],
                        $row['nisn'],
                        $row['student_name'],
                        $row['class_name'],
                        $row['violation_count'],
                        $row['violation_points'] ?? $row['total_points'],
                        $row['achievement_count'] ?? 0,
                        $row['achievement_points'] ?? 0,
                        $row['score'] ?? (($row['violation_points'] ?? $row['total_points']) - ($row['achievement_points'] ?? 0)),
                    ]);
                }

                fputcsv($out, []);
                fputcsv($out, ['=== DAFTAR PELANGGARAN ===']);
                fputcsv($out, [
                    'Tanggal', 'NIS', 'NISN', 'Nama Siswa', 'Kelas',
                    'Jenis Pelanggaran', 'Kategori', 'Poin', 'Status', 'Sanksi', 'Pelapor',
                ]);
                foreach ($data['items'] as $row) {
                    fputcsv($out, [
                        $row['violation_date'],
                        $row['nis'],
                        $row['nisn'],
                        $row['student_name'],
                        $row['class_name'],
                        $row['violation_type'],
                        $row['category'],
                        $row['point_weight'],
                        $row['status'],
                        $row['sanction'],
                        $row['reporter_name'],
                    ]);
                }

                fputcsv($out, []);
                fputcsv($out, ['=== DAFTAR PRESTASI ===']);
                fputcsv($out, [
                    'Tanggal', 'NIS', 'NISN', 'Nama Siswa', 'Kelas',
                    'Jenis Prestasi', 'Kategori', 'Poin', 'Pemberi', 'Catatan',
                ]);
                foreach ($data['achievements'] ?? [] as $row) {
                    fputcsv($out, [
                        $row['achievement_date'],
                        $row['nis'],
                        $row['nisn'],
                        $row['student_name'],
                        $row['class_name'],
                        $row['achievement_type'],
                        $row['category'],
                        $row['point_value'],
                        $row['giver_name'],
                        $row['notes'],
                    ]);
                }

                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            Log::error('BK report export violations failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengekspor detail pelanggaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * @return array<string, mixed>|null  null = scoped user tanpa akses ke filter yang diminta
     */
    protected function resolveFilters(Request $request, int $institutionId, $user): ?array
    {
        $filters = $request->only([
            'academic_year_id', 'semester_id', 'class_id', 'date_from', 'date_to', 'year', 'month',
        ]);

        // Missing key → default to active period. Empty string → explicit "all" (no filter).
        $institution = Institution::find($institutionId);
        if ($institution) {
            if (!$request->exists('academic_year_id') && $institution->active_academic_year_id) {
                $filters['academic_year_id'] = $institution->active_academic_year_id;
            }
            if (!$request->exists('semester_id') && $institution->active_semester_id) {
                $filters['semester_id'] = $institution->active_semester_id;
            }
        }

        foreach (['academic_year_id', 'semester_id', 'class_id', 'date_from', 'date_to', 'year', 'month'] as $key) {
            if (array_key_exists($key, $filters) && ($filters[$key] === '' || $filters[$key] === null)) {
                unset($filters[$key]);
            }
        }

        if (isset($filters['year'])) {
            $filters['year'] = (int) $filters['year'];
        }
        if (isset($filters['month'])) {
            $filters['month'] = (int) $filters['month'];
        }

        return WaliKelasAccess::constrainBkFilters($user, $filters);
    }

    /**
     * @return array{mode: string, homeroom_class_ids: array<int, int>}
     */
    protected function scopeMeta($user): array
    {
        $homeroomIds = WaliKelasAccess::homeroomClassIds($user)->all();

        return [
            'mode' => WaliKelasAccess::mustScopeBkToHomeroom($user) ? 'homeroom' : 'all',
            'homeroom_class_ids' => $homeroomIds,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptySummaryPayload(Request $request): array
    {
        $user = $request->user();
        $year = (int) ($request->input('year') ?: now()->year);

        return [
            'summary' => [
                'total_violations' => 0,
                'total_counseling' => 0,
                'total_achievements' => 0,
                'total_violation_points' => 0,
                'total_achievement_points' => 0,
                'net_score' => 0,
                'violations_this_month' => 0,
                'counseling_this_month' => 0,
                'achievements_this_month' => 0,
            ],
            'by_class' => [],
            'by_month' => [
                'year' => $year,
                'months' => collect(range(1, 12))->map(fn ($m) => [
                    'month' => $m,
                    'label' => now()->setMonth($m)->translatedFormat('M'),
                    'violation_count' => 0,
                    'counseling_count' => 0,
                    'achievement_count' => 0,
                ])->all(),
            ],
            'top_violation_types' => [],
            'top_achievement_types' => [],
            'filters' => [],
            'scope' => $this->scopeMeta($user),
        ];
    }
}

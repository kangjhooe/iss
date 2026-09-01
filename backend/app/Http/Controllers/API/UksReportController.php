<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Support\StructuralPositionResolver;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Services\UksReportService;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
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

    public function exportPdf(Request $request): Response|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $institution = Institution::find($institutionId);
            if (!$institution) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 404);
            }

            $filters = $this->resolveFilters($request, $institutionId);
            $mode = $request->get('mode') === 'detail' ? 'detail' : 'summary';
            $summaryPayload = $this->uksReportService->getSummary($institutionId, $filters);
            $detailPayload = [];
            if ($mode === 'detail') {
                $filters['apply_year_filter'] = true;
                $detailPayload = $this->uksReportService->getVisitDetail($institutionId, $filters);
            }

            $signers = $this->signersMeta($institutionId, StructuralPositionResolver::reportAsOfDate($filters));
            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');
            $orientation = $mode === 'detail' ? 'landscape' : 'portrait';
            $title = $mode === 'detail' ? 'Laporan UKS — Detail Kunjungan' : 'Laporan UKS — Ringkasan';

            $studentGroups = collect($detailPayload['by_student'] ?? [])
                ->groupBy(fn ($row) => $row['class_name'] ?: 'Tanpa Kelas')
                ->all();

            $pdf = DomPDF::loadView('uks.report', [
                'institution' => $institution,
                'mode' => $mode,
                'orientation' => $orientation,
                'report_title' => $title,
                'filter_legend' => $this->filterLegend($institutionId, $filters),
                'summary' => $summaryPayload['summary'] ?? [],
                'by_class' => $summaryPayload['by_class'] ?? [],
                'by_month' => $summaryPayload['by_month'] ?? ['year' => $filters['year'] ?? now()->year, 'months' => []],
                'by_type' => $summaryPayload['by_type'] ?? [],
                'by_status' => $summaryPayload['by_status'] ?? [],
                'items' => $detailPayload['items'] ?? [],
                'student_groups' => $studentGroups,
                'truncated' => $detailPayload['truncated'] ?? false,
                'printed_at' => $printedAt,
                'printed_by' => $request->user()?->name,
                'principal_role' => $signers['principal']['role'],
                'principal_name' => $signers['principal']['name'],
                'principal_nip' => $signers['principal']['nip'],
                'uks_role' => $signers['uks']['role'],
                'uks_name' => $signers['uks']['name'],
                'uks_nip' => $signers['uks']['nip'],
            ])->setPaper('a4', $orientation);

            try {
                $pdf->render();
                $canvas = $pdf->getDomPDF()->getCanvas();
                $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans');
                $x = $orientation === 'landscape' ? 720 : 500;
                $y = $orientation === 'landscape' ? 575 : 820;
                $canvas->page_text($x, $y, 'Hal. {PAGE_NUM}/{PAGE_COUNT}', $font, 7, [0.35, 0.35, 0.35]);
            } catch (\Throwable $e) {
                // nomor halaman opsional
            }

            $filename = ($mode === 'detail' ? 'laporan-uks-detail-' : 'laporan-uks-ringkasan-').date('Ymd-His').'.pdf';

            return $pdf->stream($filename, ['Attachment' => false]);
        } catch (\Exception $e) {
            Log::error('UKS report PDF failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mencetak laporan UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * @return array{principal: array{role: string, name: ?string, nip: ?string}, uks: array{role: string, name: ?string, nip: ?string}}
     */
    protected function signersMeta(int $institutionId, ?\Carbon\CarbonInterface $asOfDate = null): array
    {
        $institution = Institution::find($institutionId);
        $principal = StructuralPositionResolver::principalAt($institution, $asOfDate);
        $uks = StructuralPositionResolver::holderAt('koordinator_uks', $institutionId, $asOfDate);

        return [
            'principal' => [
                'role' => $principal['role'],
                'name' => $principal['name'],
                'nip' => $principal['nip'],
            ],
            'uks' => [
                'role' => 'Koordinator UKS',
                'name' => $uks['name'] ?? null,
                'nip' => $uks['nip'] ?? null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<string>
     */
    protected function filterLegend(int $institutionId, array $filters): array
    {
        $parts = [];
        if (!empty($filters['academic_year_id'])) {
            $year = AcademicYear::query()->where('id', $filters['academic_year_id'])->value('name');
            $parts[] = 'Tahun ajaran '.($year ?: $filters['academic_year_id']);
        }
        if (!empty($filters['semester_id'])) {
            $semester = Semester::query()->where('id', $filters['semester_id'])->value('name');
            $parts[] = 'Semester '.($semester ?: $filters['semester_id']);
        }
        if (!empty($filters['class_id'])) {
            $className = SchoolClass::query()
                ->where('institution_id', $institutionId)
                ->where('id', $filters['class_id'])
                ->value('name');
            $parts[] = 'Kelas '.($className ?: $filters['class_id']);
        } else {
            $parts[] = 'Semua kelas';
        }
        if (!empty($filters['status'])) {
            $labels = ['selesai' => 'Selesai', 'observasi' => 'Observasi', 'rujuk' => 'Rujuk'];
            $parts[] = 'Status '.($labels[$filters['status']] ?? $filters['status']);
        }
        if (!empty($filters['month']) && !empty($filters['year'])) {
            $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $parts[] = ($months[(int) $filters['month']] ?? $filters['month']).' '.$filters['year'];
        } elseif (!empty($filters['year'])) {
            $parts[] = 'Tren '.$filters['year'];
        }
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $parts[] = 'Tanggal '.($filters['date_from'] ?? '...').' s/d '.($filters['date_to'] ?? '...');
        }

        return $parts;
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

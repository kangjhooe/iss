<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Services\BkReportService;
use App\Support\StructuralPositionResolver;
use App\Support\WaliKelasAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BkReportController extends Controller
{
    use ResolvesInstitution;

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
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $this->resolveFilters($request, $institutionId, $user);
            if ($filters === null) {
                return response()->json(['data' => $this->emptySummaryPayload($request, $institutionId)]);
            }
            $data = $this->bkReportService->getSummary($institutionId, $filters);
            $data['scope'] = $this->scopeMeta($user);
            $data['signers'] = $this->signersMeta($institutionId, StructuralPositionResolver::reportAsOfDate($filters));

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
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $this->resolveFilters($request, $institutionId, $user);
            if ($filters === null) {
                return response()->json(['data' => $this->emptyDetailPayload($user, $institutionId)]);
            }
            if (!empty($filters['year'])) {
                $filters['apply_year_filter'] = true;
            }
            $data = $this->bkReportService->getViolationDetail($institutionId, $filters);
            $data['scope'] = $this->scopeMeta($user);
            $data['signers'] = $this->signersMeta($institutionId, StructuralPositionResolver::reportAsOfDate($filters));

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
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $this->resolveFilters($request, $institutionId, $user);
            if ($filters === null) {
                $rows = [];
            } else {
                $rows = $this->bkReportService->exportByClassRows($institutionId, $filters);
            }
            $reportScope = $this->resolveReportScope($request);
            $filename = 'laporan-bk-per-kelas-'.now()->format('Y-m-d').'.csv';

            return response()->streamDownload(function () use ($rows, $reportScope) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

                $headers = ['Kelas'];
                if ($reportScope !== 'achievements') {
                    $headers[] = 'Jumlah Pelanggaran';
                }
                if ($reportScope !== 'violations') {
                    $headers[] = 'Jumlah Prestasi';
                    $headers[] = 'Poin Prestasi';
                }
                if ($reportScope === 'combined') {
                    $headers[] = 'Jumlah Konseling';
                    $headers[] = 'Total Kasus';
                }
                fputcsv($out, $headers);

                foreach ($rows as $row) {
                    $line = [$row['class_name']];
                    if ($reportScope !== 'achievements') {
                        $line[] = $row['violation_count'];
                    }
                    if ($reportScope !== 'violations') {
                        $line[] = $row['achievement_count'] ?? 0;
                        $line[] = $row['achievement_points'] ?? 0;
                    }
                    if ($reportScope === 'combined') {
                        $line[] = $row['counseling_count'];
                        $line[] = $row['violation_count'] + $row['counseling_count'] + ($row['achievement_count'] ?? 0);
                    }
                    fputcsv($out, $line);
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
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $this->resolveFilters($request, $institutionId, $user);
            if ($filters === null) {
                $data = [
                    'items' => [],
                    'achievements' => [],
                    'counseling' => [],
                    'by_student' => [],
                ];
            } else {
                if (!empty($filters['year'])) {
                    $filters['apply_year_filter'] = true;
                }
                $data = $this->bkReportService->getViolationDetail($institutionId, $filters);
            }
            $reportScope = $this->resolveReportScope($request);
            $isApresiasi = $request->input('purpose') === 'apresiasi';
            $exportView = $request->input('export_view', 'catatan');
            $notesKind = $request->input('notes_kind', 'violations');
            $filename = 'laporan-bk-detail-skor-siswa-'.now()->format('Y-m-d').'.csv';

            return response()->streamDownload(function () use ($data, $reportScope, $isApresiasi, $exportView, $notesKind) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

                $includeScore = false;
                $includeViolations = false;
                $includeAchievements = false;
                $includeCounseling = false;
                $scoreIncludesAchCols = false;

                if ($reportScope === 'combined') {
                    if ($exportView === 'skor') {
                        $includeScore = true;
                        $scoreIncludesAchCols = true;
                    } elseif (in_array($notesKind, ['violations', 'achievements', 'counseling'], true)) {
                        $includeViolations = $notesKind === 'violations';
                        $includeAchievements = $notesKind === 'achievements';
                        $includeCounseling = $notesKind === 'counseling';
                    }
                } elseif ($reportScope === 'violations') {
                    if ($exportView === 'skor') {
                        $includeScore = true;
                        $scoreIncludesAchCols = true;
                    } else {
                        $includeViolations = true;
                    }
                } elseif ($reportScope === 'achievements') {
                    $includeAchievements = true;
                }

                if ($includeScore) {
                    fputcsv($out, ['=== REKAP SKOR PER SISWA ===']);
                    $scoreHeaders = ['NIS', 'NISN', 'Nama Siswa', 'Kelas', 'Jml Pelanggaran', 'Poin Pelanggaran'];
                    if ($scoreIncludesAchCols) {
                        $scoreHeaders[] = 'Jml Prestasi';
                        $scoreHeaders[] = 'Poin Prestasi';
                    }
                    $scoreHeaders[] = 'Skor';
                    fputcsv($out, $scoreHeaders);
                    foreach ($data['by_student'] as $row) {
                        $line = [
                            $row['nis'],
                            $row['nisn'],
                            $row['student_name'],
                            $row['class_name'],
                            $row['violation_count'],
                            $row['violation_points'] ?? $row['total_points'],
                        ];
                        if ($scoreIncludesAchCols) {
                            $line[] = $row['achievement_count'] ?? 0;
                            $line[] = $row['achievement_points'] ?? 0;
                        }
                        $line[] = $row['score'] ?? (($row['violation_points'] ?? $row['total_points']) - ($row['achievement_points'] ?? 0));
                        fputcsv($out, $line);
                    }
                    fputcsv($out, []);
                }

                if ($includeViolations) {
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
                }

                if ($includeAchievements) {
                    fputcsv($out, [$isApresiasi ? '=== DAFTAR APRESIASI ===' : '=== DAFTAR PRESTASI ===']);
                    $achHeaders = [
                        'Tanggal', 'NIS', 'NISN', 'Nama Siswa', 'Kelas',
                        $isApresiasi ? 'Uraian' : 'Lomba / Kegiatan',
                        'Jenis Prestasi',
                    ];
                    if (!$isApresiasi) {
                        $achHeaders[] = 'Tingkat';
                        $achHeaders[] = 'Peringkat';
                    }
                    $achHeaders = array_merge($achHeaders, ['Kategori', 'Poin', 'Pemberi', 'Catatan']);
                    fputcsv($out, $achHeaders);
                    foreach ($data['achievements'] ?? [] as $row) {
                        $line = [
                            $row['achievement_date'],
                            $row['nis'],
                            $row['nisn'],
                            $row['student_name'],
                            $row['class_name'],
                            $row['title'] ?? $row['notes'] ?? '',
                            $row['achievement_type'],
                        ];
                        if (!$isApresiasi) {
                            $line[] = $row['level'] ?? '';
                            $line[] = $row['rank'] ?? '';
                        }
                        $line[] = $row['category'];
                        $line[] = $row['point_value'];
                        $line[] = $row['giver_name'];
                        $line[] = $row['notes'];
                        fputcsv($out, $line);
                    }
                    fputcsv($out, []);
                }

                if ($includeCounseling) {
                    fputcsv($out, ['=== DAFTAR KONSELING ===']);
                    fputcsv($out, [
                        'Tanggal', 'NIS', 'NISN', 'Nama Siswa', 'Kelas',
                        'Jenis Konseling', 'Status', 'Konselor', 'Ringkasan',
                    ]);
                    foreach ($data['counseling'] ?? [] as $row) {
                        fputcsv($out, [
                            $row['session_date'],
                            $row['nis'],
                            $row['nisn'],
                            $row['student_name'],
                            $row['class_name'],
                            $row['counseling_type'],
                            $row['status'],
                            $row['counselor_name'],
                            $row['summary'],
                        ]);
                    }
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
            'academic_year_id', 'semester_id', 'class_id', 'date_from', 'date_to', 'year', 'month', 'purpose',
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

        foreach (['academic_year_id', 'semester_id', 'class_id', 'date_from', 'date_to', 'year', 'month', 'purpose'] as $key) {
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

    protected function resolveReportScope(Request $request): string
    {
        $scope = $request->input('report_scope', 'combined');

        return in_array($scope, ['combined', 'violations', 'achievements'], true) ? $scope : 'combined';
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
     * @return array{principal: array{role: string, name: ?string, nip: ?string}, bk: array{role: string, name: ?string, nip: ?string}}
     */
    protected function signersMeta(int $institutionId, ?\Carbon\CarbonInterface $asOfDate = null): array
    {
        $institution = Institution::find($institutionId);
        $principal = StructuralPositionResolver::principalAt($institution, $asOfDate);
        $bk = StructuralPositionResolver::holderAt('koordinator_bk', $institutionId, $asOfDate);

        return [
            'principal' => [
                'role' => $principal['role'],
                'name' => $principal['name'],
                'nip' => $principal['nip'],
            ],
            'bk' => [
                'role' => 'Guru Bimbingan Konseling',
                'name' => $bk['name'] ?? null,
                'nip' => $bk['nip'] ?? null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyDetailPayload($user, int $institutionId): array
    {
        return [
            'items' => [],
            'achievements' => [],
            'counseling' => [],
            'by_student' => [],
            'total' => 0,
            'achievements_total' => 0,
            'counseling_total' => 0,
            'truncated' => false,
            'scope' => $this->scopeMeta($user),
            'signers' => $this->signersMeta($institutionId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptySummaryPayload(Request $request, ?int $institutionId = null): array
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
            'signers' => $institutionId ? $this->signersMeta($institutionId) : [
                'principal' => ['role' => Institution::principalTitleForLevel(null), 'name' => null, 'nip' => null],
                'bk' => ['role' => 'Guru Bimbingan Konseling', 'name' => null, 'nip' => null],
            ],
        ];
    }
}

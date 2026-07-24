<?php

namespace App\Http\Controllers\API;

use App\Exports\AggregateReportExport;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SuperAdminReportController extends Controller
{
    private function ensureSuperAdmin(Request $request): void
    {
        if (!$request->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Aggregate cross-institution report.
     */
    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        try {
            return response()->json([
                'data' => $this->buildReport($request),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load aggregate report', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memuat laporan agregat',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export aggregate report as XLSX.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $this->ensureSuperAdmin($request);

        $report = $this->buildReport($request);
        $filename = 'laporan-agregat-' . now()->format('Ymd-His') . '.xlsx';

        return app(ExcelManager::class)->download(
            new AggregateReportExport($report),
            $filename,
            ExcelManager::XLSX
        );
    }

    private function buildReport(Request $request): array
    {
        $levelFilter = $request->get('level');
        $typeFilter = $request->get('type');
        $activeFilter = $request->has('is_active')
            ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)
            : null;

        $instQuery = Institution::query();
        if ($levelFilter) {
            $instQuery->where('level', $levelFilter);
        }
        if ($typeFilter) {
            $instQuery->where('type', $typeFilter);
        }
        if ($activeFilter !== null) {
            $instQuery->where('is_active', $activeFilter);
        }

        $institutionIds = (clone $instQuery)->pluck('id');

        $studentCounts = Student::query()
            ->select('institution_id', DB::raw('COUNT(*) as total'))
            ->where('status', 'Aktif')
            ->when($institutionIds->isNotEmpty(), fn ($q) => $q->whereIn('institution_id', $institutionIds))
            ->groupBy('institution_id')
            ->pluck('total', 'institution_id');

        $teacherCounts = Employee::query()
            ->select('institution_id', DB::raw('COUNT(*) as total'))
            ->where('type', 'Guru')
            ->where('status', 'Aktif')
            ->when($institutionIds->isNotEmpty(), fn ($q) => $q->whereIn('institution_id', $institutionIds))
            ->groupBy('institution_id')
            ->pluck('total', 'institution_id');

        $institutions = $instQuery
            ->orderBy('name')
            ->get(['id', 'name', 'npsn', 'level', 'type', 'province', 'phone', 'email', 'is_active'])
            ->map(function ($inst) use ($studentCounts, $teacherCounts) {
                return [
                    'id' => $inst->id,
                    'name' => $inst->name,
                    'npsn' => $inst->npsn,
                    'level' => $inst->level,
                    'type' => $inst->type,
                    'province' => $inst->province,
                    'phone' => $inst->phone,
                    'email' => $inst->email,
                    'is_active' => (bool) $inst->is_active,
                    'students' => (int) ($studentCounts[$inst->id] ?? 0),
                    'teachers' => (int) ($teacherCounts[$inst->id] ?? 0),
                ];
            });

        $byLevel = [];
        $byType = [];
        $byProvince = [];

        foreach ($institutions as $row) {
            $lvl = $row['level'] ?: 'Tidak diisi';
            $typ = $row['type'] ?: 'Tidak diisi';
            $prov = $row['province'] ?: 'Tidak diisi';

            if (!isset($byLevel[$lvl])) {
                $byLevel[$lvl] = ['level' => $lvl, 'institutions' => 0, 'students' => 0, 'teachers' => 0];
            }
            $byLevel[$lvl]['institutions']++;
            $byLevel[$lvl]['students'] += $row['students'];
            $byLevel[$lvl]['teachers'] += $row['teachers'];

            if (!isset($byType[$typ])) {
                $byType[$typ] = ['type' => $typ, 'institutions' => 0, 'students' => 0, 'teachers' => 0];
            }
            $byType[$typ]['institutions']++;
            $byType[$typ]['students'] += $row['students'];
            $byType[$typ]['teachers'] += $row['teachers'];

            if (!isset($byProvince[$prov])) {
                $byProvince[$prov] = ['province' => $prov, 'institutions' => 0, 'students' => 0, 'teachers' => 0];
            }
            $byProvince[$prov]['institutions']++;
            $byProvince[$prov]['students'] += $row['students'];
            $byProvince[$prov]['teachers'] += $row['teachers'];
        }

        usort($byLevel, fn ($a, $b) => $b['institutions'] <=> $a['institutions']);
        usort($byType, fn ($a, $b) => $b['institutions'] <=> $a['institutions']);
        usort($byProvince, fn ($a, $b) => $b['institutions'] <=> $a['institutions']);

        return [
            'summary' => [
                'institutions' => $institutions->count(),
                'active_institutions' => $institutions->where('is_active', true)->count(),
                'students' => $institutions->sum('students'),
                'teachers' => $institutions->sum('teachers'),
            ],
            'by_level' => array_values($byLevel),
            'by_type' => array_values($byType),
            'by_province' => array_values($byProvince),
            'institutions' => $institutions->values(),
        ];
    }
}

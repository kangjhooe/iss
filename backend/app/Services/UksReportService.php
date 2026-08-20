<?php

namespace App\Services;

use App\Models\UksVisit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UksReportService
{
    /**
     * @param  array{academic_year_id?: mixed, semester_id?: mixed, class_id?: mixed, date_from?: string, date_to?: string, year?: int, uks_visit_type_id?: mixed, status?: string}  $filters
     */
    public function getSummary(int $institutionId, array $filters = []): array
    {
        $base = $this->baseQuery($institutionId, $filters);

        $totalVisits = (clone $base)->count();
        $visitsThisMonth = (clone $base)
            ->whereMonth('visit_date', Carbon::now()->month)
            ->whereYear('visit_date', Carbon::now()->year)
            ->count();
        $totalReferral = (clone $base)->where('status', 'rujuk')->count();
        $totalObservation = (clone $base)->where('status', 'observasi')->count();
        $studentsServed = (int) (clone $base)->selectRaw('COUNT(DISTINCT student_id) as aggregate')->value('aggregate');

        return [
            'summary' => [
                'total_visits' => $totalVisits,
                'visits_this_month' => $visitsThisMonth,
                'total_referral' => $totalReferral,
                'total_observation' => $totalObservation,
                'students_served' => $studentsServed,
            ],
            'by_class' => $this->byClass($institutionId, $filters),
            'by_month' => $this->byMonth($institutionId, $filters),
            'by_type' => $this->byType($institutionId, $filters),
            'by_status' => $this->byStatus($institutionId, $filters),
            'filters' => [
                'academic_year_id' => $filters['academic_year_id'] ?? null,
                'semester_id' => $filters['semester_id'] ?? null,
                'class_id' => $filters['class_id'] ?? null,
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
                'year' => $filters['year'] ?? null,
                'uks_visit_type_id' => $filters['uks_visit_type_id'] ?? null,
                'status' => $filters['status'] ?? null,
            ],
        ];
    }

    public function getVisitDetail(int $institutionId, array $filters = []): array
    {
        $query = $this->baseQuery($institutionId, $filters)
            ->with([
                'student:id,name,nis,nisn,class_id',
                'student.class:id,name',
                'visitType:id,name,code',
                'recorder:id,name',
            ])
            ->orderBy('visit_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(2000);

        $items = $query->get()->map(fn (UksVisit $v) => [
            'id' => $v->id,
            'visit_date' => $v->visit_date?->format('Y-m-d'),
            'student_id' => $v->student_id,
            'student_name' => $v->student?->name,
            'nis' => $v->student?->nis,
            'nisn' => $v->student?->nisn,
            'class_name' => $v->student?->class?->name ?? $v->schoolClass?->name ?? 'Tanpa Kelas',
            'visit_type' => $v->visitType?->name ?? '-',
            'status' => $v->status,
            'complaint' => $v->complaint,
            'action_taken' => $v->action_taken,
            'notes' => $v->notes,
            'recorder_name' => $v->recorder?->name,
            'height_cm' => $v->height_cm,
            'weight_kg' => $v->weight_kg,
            'temperature_c' => $v->temperature_c,
            'blood_pressure' => $v->blood_pressure,
        ])->values()->all();

        $byStudent = collect($items)
            ->groupBy('student_id')
            ->map(function ($rows, $studentId) {
                $first = $rows->first();

                return [
                    'student_id' => $studentId,
                    'student_name' => $first['student_name'],
                    'nis' => $first['nis'],
                    'class_name' => $first['class_name'],
                    'visit_count' => $rows->count(),
                    'referral_count' => $rows->where('status', 'rujuk')->count(),
                ];
            })
            ->sort(function ($a, $b) {
                $class = strnatcasecmp((string) ($a['class_name'] ?? ''), (string) ($b['class_name'] ?? ''));
                if ($class !== 0) {
                    return $class;
                }
                $visits = ($b['visit_count'] <=> $a['visit_count']);
                if ($visits !== 0) {
                    return $visits;
                }

                return strcasecmp((string) ($a['student_name'] ?? ''), (string) ($b['student_name'] ?? ''));
            })
            ->values()
            ->all();

        return [
            'items' => $items,
            'by_student' => $byStudent,
            'total' => count($items),
            'truncated' => count($items) >= 2000,
        ];
    }

    public function byClass(int $institutionId, array $filters = []): array
    {
        return $this->baseQuery($institutionId, $filters)
            ->leftJoin('class', 'uks_visits.class_id', '=', 'class.id')
            ->select(
                'uks_visits.class_id',
                DB::raw("COALESCE(class.name, 'Tanpa Kelas') as class_name"),
                DB::raw('COUNT(*) as visit_count'),
                DB::raw("SUM(CASE WHEN uks_visits.status = 'rujuk' THEN 1 ELSE 0 END) as referral_count")
            )
            ->groupBy('uks_visits.class_id', 'class.name')
            ->orderByDesc('visit_count')
            ->get()
            ->map(fn ($r) => [
                'class_id' => $r->class_id,
                'class_name' => $r->class_name,
                'visit_count' => (int) $r->visit_count,
                'referral_count' => (int) $r->referral_count,
            ])
            ->values()
            ->all();
    }

    public function byMonth(int $institutionId, array $filters = []): array
    {
        $year = (int) ($filters['year'] ?? Carbon::now()->year);
        $monthFilters = $filters;
        unset($monthFilters['date_from'], $monthFilters['date_to']);

        $rows = $this->baseQuery($institutionId, $monthFilters)
            ->whereYear('visit_date', $year)
            ->select(
                DB::raw('MONTH(visit_date) as month'),
                DB::raw('COUNT(*) as visit_count'),
                DB::raw("SUM(CASE WHEN status = 'rujuk' THEN 1 ELSE 0 END) as referral_count")
            )
            ->groupBy(DB::raw('MONTH(visit_date)'))
            ->get()
            ->keyBy('month');

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $row = $rows->get($m);
            $months[] = [
                'month' => $m,
                'visit_count' => (int) ($row->visit_count ?? 0),
                'referral_count' => (int) ($row->referral_count ?? 0),
            ];
        }

        return ['year' => $year, 'months' => $months];
    }

    public function byType(int $institutionId, array $filters = []): array
    {
        return $this->baseQuery($institutionId, $filters)
            ->leftJoin('uks_visit_types', 'uks_visits.uks_visit_type_id', '=', 'uks_visit_types.id')
            ->select(
                'uks_visits.uks_visit_type_id',
                DB::raw("COALESCE(uks_visit_types.name, 'Tanpa jenis') as type_name"),
                DB::raw('COUNT(*) as visit_count')
            )
            ->groupBy('uks_visits.uks_visit_type_id', 'uks_visit_types.name')
            ->orderByDesc('visit_count')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'uks_visit_type_id' => $r->uks_visit_type_id,
                'type_name' => $r->type_name,
                'visit_count' => (int) $r->visit_count,
            ])
            ->values()
            ->all();
    }

    public function byStatus(int $institutionId, array $filters = []): array
    {
        return $this->baseQuery($institutionId, $filters)
            ->select('status', DB::raw('COUNT(*) as visit_count'))
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => [
                'status' => $r->status,
                'visit_count' => (int) $r->visit_count,
            ])
            ->values()
            ->all();
    }

    protected function baseQuery(int $institutionId, array $filters)
    {
        $query = UksVisit::query()->where('uks_visits.institution_id', $institutionId);

        if (!empty($filters['academic_year_id'])) {
            $query->where('uks_visits.academic_year_id', $filters['academic_year_id']);
        }
        if (!empty($filters['semester_id'])) {
            $query->where('uks_visits.semester_id', $filters['semester_id']);
        }
        if (!empty($filters['class_id'])) {
            $query->where('uks_visits.class_id', $filters['class_id']);
        }
        if (!empty($filters['uks_visit_type_id'])) {
            $query->where('uks_visits.uks_visit_type_id', $filters['uks_visit_type_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('uks_visits.status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('uks_visits.visit_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('uks_visits.visit_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['month']) && !empty($filters['year'])) {
            $query->whereMonth('uks_visits.visit_date', (int) $filters['month'])
                ->whereYear('uks_visits.visit_date', (int) $filters['year']);
        } elseif (!empty($filters['apply_year_filter']) && !empty($filters['year']) && empty($filters['date_from']) && empty($filters['date_to'])) {
            $query->whereYear('uks_visits.visit_date', (int) $filters['year']);
        }

        return $query;
    }
}

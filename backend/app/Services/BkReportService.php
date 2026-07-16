<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\CounselingSession;
use App\Models\Violation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BkReportService
{
    /**
     * Aggregate BK report: summary, by class, by month, top violation/achievement types.
     *
     * @param  array{academic_year_id?: mixed, semester_id?: mixed, class_id?: mixed, date_from?: string, date_to?: string, year?: int}  $filters
     */
    public function getSummary(int $institutionId, array $filters = []): array
    {
        $violationQuery = $this->baseViolationQuery($institutionId, $filters);
        $counselingQuery = $this->baseCounselingQuery($institutionId, $filters);
        $achievementQuery = $this->baseAchievementQuery($institutionId, $filters);

        $totalViolations = (clone $violationQuery)->count();
        $totalCounseling = (clone $counselingQuery)->count();
        $totalAchievements = (clone $achievementQuery)->count();
        $totalAchievementPoints = (int) (clone $achievementQuery)->sum('achievements.point_value');
        $totalViolationPoints = (int) (clone $violationQuery)
            ->leftJoin('violation_types', 'violations.violation_type_id', '=', 'violation_types.id')
            ->sum('violation_types.point_weight');

        $violationsThisMonth = (clone $violationQuery)
            ->whereMonth('violation_date', Carbon::now()->month)
            ->whereYear('violation_date', Carbon::now()->year)
            ->count();

        $counselingThisMonth = (clone $counselingQuery)
            ->whereMonth('session_date', Carbon::now()->month)
            ->whereYear('session_date', Carbon::now()->year)
            ->count();

        $achievementsThisMonth = (clone $achievementQuery)
            ->whereMonth('achievement_date', Carbon::now()->month)
            ->whereYear('achievement_date', Carbon::now()->year)
            ->count();

        return [
            'summary' => [
                'total_violations' => $totalViolations,
                'total_counseling' => $totalCounseling,
                'total_achievements' => $totalAchievements,
                'total_violation_points' => $totalViolationPoints,
                'total_achievement_points' => $totalAchievementPoints,
                'net_score' => $totalViolationPoints - $totalAchievementPoints,
                'violations_this_month' => $violationsThisMonth,
                'counseling_this_month' => $counselingThisMonth,
                'achievements_this_month' => $achievementsThisMonth,
            ],
            'by_class' => $this->byClass($institutionId, $filters),
            'by_month' => $this->byMonth($institutionId, $filters),
            'top_violation_types' => $this->topViolationTypes($institutionId, $filters),
            'top_achievement_types' => $this->topAchievementTypes($institutionId, $filters),
            'filters' => [
                'academic_year_id' => $filters['academic_year_id'] ?? null,
                'semester_id' => $filters['semester_id'] ?? null,
                'class_id' => $filters['class_id'] ?? null,
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
                'year' => $filters['year'] ?? null,
            ],
        ];
    }

    /**
     * Rekap pelanggaran, prestasi & konseling per kelas.
     */
    public function byClass(int $institutionId, array $filters = []): array
    {
        $violationRows = $this->baseViolationQuery($institutionId, $filters)
            ->leftJoin('class', 'violations.class_id', '=', 'class.id')
            ->select(
                'violations.class_id',
                DB::raw("COALESCE(class.name, 'Tanpa Kelas') as class_name"),
                DB::raw('COUNT(*) as violation_count')
            )
            ->groupBy('violations.class_id', 'class.name')
            ->get()
            ->keyBy(fn ($r) => $r->class_id ?? 0);

        $counselingRows = $this->baseCounselingQuery($institutionId, $filters)
            ->leftJoin('class', 'counseling_sessions.class_id', '=', 'class.id')
            ->select(
                'counseling_sessions.class_id',
                DB::raw("COALESCE(class.name, 'Tanpa Kelas') as class_name"),
                DB::raw('COUNT(*) as counseling_count')
            )
            ->groupBy('counseling_sessions.class_id', 'class.name')
            ->get()
            ->keyBy(fn ($r) => $r->class_id ?? 0);

        $achievementRows = $this->baseAchievementQuery($institutionId, $filters)
            ->leftJoin('student', 'achievements.student_id', '=', 'student.id')
            ->leftJoin('class', 'student.class_id', '=', 'class.id')
            ->select(
                'student.class_id',
                DB::raw("COALESCE(class.name, 'Tanpa Kelas') as class_name"),
                DB::raw('COUNT(*) as achievement_count'),
                DB::raw('COALESCE(SUM(achievements.point_value), 0) as achievement_points')
            )
            ->groupBy('student.class_id', 'class.name')
            ->get()
            ->keyBy(fn ($r) => $r->class_id ?? 0);

        $keys = $violationRows->keys()
            ->merge($counselingRows->keys())
            ->merge($achievementRows->keys())
            ->unique()
            ->values();

        $result = [];
        foreach ($keys as $key) {
            $v = $violationRows->get($key);
            $c = $counselingRows->get($key);
            $a = $achievementRows->get($key);
            $result[] = [
                'class_id' => $key ?: null,
                'class_name' => $v?->class_name ?? $c?->class_name ?? $a?->class_name ?? 'Tanpa Kelas',
                'violation_count' => (int) ($v?->violation_count ?? 0),
                'counseling_count' => (int) ($c?->counseling_count ?? 0),
                'achievement_count' => (int) ($a?->achievement_count ?? 0),
                'achievement_points' => (int) ($a?->achievement_points ?? 0),
            ];
        }

        usort($result, fn ($a, $b) => $b['violation_count'] <=> $a['violation_count']
            ?: $b['achievement_count'] <=> $a['achievement_count']
            ?: $b['counseling_count'] <=> $a['counseling_count']
            ?: strcmp($a['class_name'], $b['class_name']));

        return $result;
    }

    /**
     * Tren pelanggaran, prestasi & konseling per bulan (kalender).
     */
    public function byMonth(int $institutionId, array $filters = []): array
    {
        $year = !empty($filters['year'])
            ? (int) $filters['year']
            : (int) Carbon::now()->year;

        $monthFilters = $filters;
        unset($monthFilters['year']);

        $violationMonths = $this->baseViolationQuery($institutionId, $monthFilters)
            ->whereYear('violation_date', $year)
            ->select(
                DB::raw('MONTH(violation_date) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('MONTH(violation_date)'))
            ->pluck('count', 'month');

        $counselingMonths = $this->baseCounselingQuery($institutionId, $monthFilters)
            ->whereYear('session_date', $year)
            ->select(
                DB::raw('MONTH(session_date) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('MONTH(session_date)'))
            ->pluck('count', 'month');

        $achievementMonths = $this->baseAchievementQuery($institutionId, $monthFilters)
            ->whereYear('achievement_date', $year)
            ->select(
                DB::raw('MONTH(achievement_date) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('MONTH(achievement_date)'))
            ->pluck('count', 'month');

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = [
                'month' => $m,
                'label' => Carbon::create($year, $m, 1)->translatedFormat('M'),
                'violation_count' => (int) ($violationMonths[$m] ?? 0),
                'counseling_count' => (int) ($counselingMonths[$m] ?? 0),
                'achievement_count' => (int) ($achievementMonths[$m] ?? 0),
            ];
        }

        return [
            'year' => $year,
            'months' => $months,
        ];
    }

    /**
     * Top jenis pelanggaran.
     */
    public function topViolationTypes(int $institutionId, array $filters = [], int $limit = 10): array
    {
        return $this->baseViolationQuery($institutionId, $filters)
            ->leftJoin('violation_types', 'violations.violation_type_id', '=', 'violation_types.id')
            ->select(
                'violations.violation_type_id',
                DB::raw("COALESCE(violation_types.name, 'Tanpa Jenis') as type_name"),
                DB::raw("COALESCE(violation_types.category, '-') as category"),
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(violation_types.point_weight), 0) as total_points')
            )
            ->groupBy('violations.violation_type_id', 'violation_types.name', 'violation_types.category')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'violation_type_id' => $r->violation_type_id,
                'type_name' => $r->type_name,
                'category' => $r->category,
                'count' => (int) $r->count,
                'total_points' => (int) $r->total_points,
            ])
            ->values()
            ->all();
    }

    /**
     * Top jenis prestasi.
     */
    public function topAchievementTypes(int $institutionId, array $filters = [], int $limit = 10): array
    {
        return $this->baseAchievementQuery($institutionId, $filters)
            ->leftJoin('achievement_types', 'achievements.achievement_type_id', '=', 'achievement_types.id')
            ->select(
                'achievements.achievement_type_id',
                DB::raw("COALESCE(achievement_types.name, 'Tanpa Jenis') as type_name"),
                DB::raw("COALESCE(achievement_types.category, '-') as category"),
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(achievements.point_value), 0) as total_points')
            )
            ->groupBy('achievements.achievement_type_id', 'achievement_types.name', 'achievement_types.category')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'achievement_type_id' => $r->achievement_type_id,
                'type_name' => $r->type_name,
                'category' => $r->category,
                'count' => (int) $r->count,
                'total_points' => (int) $r->total_points,
            ])
            ->values()
            ->all();
    }

    /**
     * Flat rows for CSV export (by class rekap).
     */
    public function exportByClassRows(int $institutionId, array $filters = []): array
    {
        return $this->byClass($institutionId, $filters);
    }

    /**
     * Detail: daftar pelanggaran, daftar prestasi, rekap skor per siswa.
     *
     * @return array{items: array, achievements: array, by_student: array, total: int, achievements_total: int, truncated: bool}
     */
    public function getViolationDetail(int $institutionId, array $filters = [], int $limit = 2000): array
    {
        $violations = $this->baseViolationQuery($institutionId, $filters)
            ->with([
                'student:id,name,nis,nisn',
                'violationType:id,name,category,point_weight',
                'reporter:id,name',
                'schoolClass:id,name',
            ])
            ->orderByDesc('violations.violation_date')
            ->orderByDesc('violations.id')
            ->limit($limit)
            ->get();

        $items = $violations->map(function (Violation $v) {
            return [
                'id' => $v->id,
                'violation_date' => $v->violation_date?->format('Y-m-d'),
                'student_id' => $v->student_id,
                'nis' => $v->student?->nis,
                'nisn' => $v->student?->nisn,
                'student_name' => $v->student?->name,
                'class_id' => $v->class_id,
                'class_name' => $v->schoolClass?->name ?? 'Tanpa Kelas',
                'violation_type' => $v->violationType?->name ?? '-',
                'category' => $v->violationType?->category ?? '-',
                'point_weight' => (int) ($v->violationType?->point_weight ?? 0),
                'status' => $v->status,
                'sanction' => $v->sanction,
                'reporter_name' => $v->reporter?->name,
                'description' => $v->description,
            ];
        })->values()->all();

        $achievements = $this->baseAchievementQuery($institutionId, $filters)
            ->with([
                'student:id,name,nis,nisn,class_id',
                'student.class:id,name',
                'achievementType:id,name,category,point_value',
                'giver:id,name',
            ])
            ->orderByDesc('achievements.achievement_date')
            ->orderByDesc('achievements.id')
            ->limit($limit)
            ->get();

        $achievementItems = $achievements->map(function (Achievement $a) {
            $className = null;
            if ($a->relationLoaded('student') && $a->student) {
                $classRel = $a->student->relationLoaded('class') ? $a->student->getRelation('class') : null;
                if ($classRel && is_object($classRel) && isset($classRel->name)) {
                    $className = $classRel->name;
                }
            }

            return [
                'id' => $a->id,
                'achievement_date' => $a->achievement_date?->format('Y-m-d'),
                'student_id' => $a->student_id,
                'nis' => $a->student?->nis,
                'nisn' => $a->student?->nisn,
                'student_name' => $a->student?->name,
                'class_id' => $a->student?->class_id,
                'class_name' => $className ?? 'Tanpa Kelas',
                'achievement_type' => $a->achievementType?->name ?? '-',
                'category' => $a->achievementType?->category ?? '-',
                'point_value' => (int) $a->point_value,
                'notes' => $a->notes,
                'giver_name' => $a->giver?->name,
            ];
        })->values()->all();

        $byStudentMap = [];

        foreach ($items as $item) {
            $sid = $item['student_id'] ?? 0;
            if (!isset($byStudentMap[$sid])) {
                $byStudentMap[$sid] = $this->emptyStudentScoreRow($item);
            }
            $byStudentMap[$sid]['violation_count']++;
            $byStudentMap[$sid]['violation_points'] += $item['point_weight'];
        }

        foreach ($achievementItems as $item) {
            $sid = $item['student_id'] ?? 0;
            if (!isset($byStudentMap[$sid])) {
                $byStudentMap[$sid] = $this->emptyStudentScoreRow($item);
            }
            $byStudentMap[$sid]['achievement_count']++;
            $byStudentMap[$sid]['achievement_points'] += $item['point_value'];
        }

        $byStudent = array_values(array_map(function (array $row) {
            $row['score'] = $row['violation_points'] - $row['achievement_points'];
            // backward-compatible alias
            $row['total_points'] = $row['violation_points'];

            return $row;
        }, $byStudentMap));

        usort($byStudent, fn ($a, $b) => $b['score'] <=> $a['score']
            ?: $b['violation_count'] <=> $a['violation_count']
            ?: strcmp($a['student_name'] ?? '', $b['student_name'] ?? ''));

        return [
            'items' => $items,
            'achievements' => $achievementItems,
            'by_student' => $byStudent,
            'total' => count($items),
            'achievements_total' => count($achievementItems),
            'truncated' => count($items) >= $limit || count($achievementItems) >= $limit,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function emptyStudentScoreRow(array $item): array
    {
        return [
            'student_id' => $item['student_id'],
            'nis' => $item['nis'],
            'nisn' => $item['nisn'],
            'student_name' => $item['student_name'],
            'class_name' => $item['class_name'],
            'violation_count' => 0,
            'violation_points' => 0,
            'achievement_count' => 0,
            'achievement_points' => 0,
            'score' => 0,
            'total_points' => 0,
        ];
    }

    protected function baseViolationQuery(int $institutionId, array $filters)
    {
        $query = Violation::query()
            ->from('violations')
            ->where('violations.institution_id', $institutionId)
            ->whereIn('violations.status', Violation::STATUSES_COUNTING_POINTS);

        $this->applyCommonFilters($query, 'violations', 'violation_date', $filters);

        return $query;
    }

    protected function baseCounselingQuery(int $institutionId, array $filters)
    {
        $query = CounselingSession::query()
            ->from('counseling_sessions')
            ->where('counseling_sessions.institution_id', $institutionId);

        $this->applyCommonFilters($query, 'counseling_sessions', 'session_date', $filters);

        return $query;
    }

    protected function baseAchievementQuery(int $institutionId, array $filters)
    {
        $query = Achievement::query()
            ->from('achievements')
            ->where('achievements.institution_id', $institutionId);

        $this->applyCommonFilters($query, 'achievements', 'achievement_date', $filters, false);

        if (!empty($filters['class_ids']) && is_array($filters['class_ids'])) {
            $query->whereExists(function ($q) use ($filters) {
                $q->select(DB::raw(1))
                    ->from('student')
                    ->whereColumn('student.id', 'achievements.student_id')
                    ->whereIn('student.class_id', $filters['class_ids']);
            });
        } elseif (!empty($filters['class_id'])) {
            $query->whereExists(function ($q) use ($filters) {
                $q->select(DB::raw(1))
                    ->from('student')
                    ->whereColumn('student.id', 'achievements.student_id')
                    ->where('student.class_id', $filters['class_id']);
            });
        }

        return $query;
    }

    protected function applyCommonFilters(
        $query,
        string $table,
        string $dateColumn,
        array $filters,
        bool $applyClassOnTable = true
    ): void {
        if (!empty($filters['academic_year_id'])) {
            $query->where("{$table}.academic_year_id", $filters['academic_year_id']);
        }
        if (!empty($filters['semester_id'])) {
            $query->where("{$table}.semester_id", $filters['semester_id']);
        }
        if ($applyClassOnTable) {
            if (!empty($filters['class_ids']) && is_array($filters['class_ids'])) {
                $query->whereIn("{$table}.class_id", $filters['class_ids']);
            } elseif (!empty($filters['class_id'])) {
                $query->where("{$table}.class_id", $filters['class_id']);
            }
        }

        if (!empty($filters['month']) && !empty($filters['year'])) {
            $query->whereMonth("{$table}.{$dateColumn}", (int) $filters['month'])
                ->whereYear("{$table}.{$dateColumn}", (int) $filters['year']);
        } elseif (!empty($filters['year']) && !empty($filters['apply_year_filter'])) {
            $query->whereYear("{$table}.{$dateColumn}", (int) $filters['year']);
        } else {
            if (!empty($filters['date_from'])) {
                $query->whereDate("{$table}.{$dateColumn}", '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate("{$table}.{$dateColumn}", '<=', $filters['date_to']);
            }
        }
    }
}

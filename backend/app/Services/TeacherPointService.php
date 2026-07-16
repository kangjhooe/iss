<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\PiketIncident;
use App\Models\TeacherAchievement;
use App\Models\TeacherAchievementType;
use App\Models\TeacherPointReward;
use App\Models\TeacherRewardLog;
use App\Models\TeacherViolation;
use App\Models\TeacherViolationType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherPointService
{
    /**
     * Poin prestasi approved (positif).
     */
    public function getAchievementPoints(
        int $employeeId,
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null
    ): int {
        $query = TeacherAchievement::forInstitution($institutionId)
            ->forEmployee($employeeId)
            ->approved();

        $this->applyPeriodFilters($query, $academicYearId, $semesterId);

        return (int) $query->sum('point_value');
    }

    /**
     * Poin pelanggaran approved (nilai positif = bobot minus).
     */
    public function getViolationPoints(
        int $employeeId,
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null
    ): int {
        $query = TeacherViolation::forInstitution($institutionId)
            ->forEmployee($employeeId)
            ->approved();

        $this->applyPeriodFilters($query, $academicYearId, $semesterId);

        return (int) $query->sum('point_value');
    }

    /**
     * Total neto = prestasi − pelanggaran (prestasi bisa menutup pelanggaran).
     */
    public function getTotalPoints(
        int $employeeId,
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null
    ): int {
        return $this->getAchievementPoints($employeeId, $institutionId, $academicYearId, $semesterId)
            - $this->getViolationPoints($employeeId, $institutionId, $academicYearId, $semesterId);
    }

    public function getPointsByCategory(
        int $employeeId,
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null
    ): array {
        $query = TeacherAchievement::query()
            ->from('teacher_achievements')
            ->join('teacher_achievement_types', 'teacher_achievements.achievement_type_id', '=', 'teacher_achievement_types.id')
            ->where('teacher_achievements.institution_id', $institutionId)
            ->where('teacher_achievements.employee_id', $employeeId)
            ->where('teacher_achievements.status', TeacherAchievement::STATUS_APPROVED)
            ->select(
                'teacher_achievement_types.category',
                DB::raw('SUM(teacher_achievements.point_value) as points'),
                DB::raw('COUNT(teacher_achievements.id) as count')
            )
            ->groupBy('teacher_achievement_types.category');

        $this->applyPeriodFilters($query, $academicYearId, $semesterId, 'teacher_achievements');

        return $query->get()->map(fn ($row) => [
            'category' => $row->category ?: 'lainnya',
            'points' => (int) $row->points,
            'count' => (int) $row->count,
        ])->values()->all();
    }

    public function getViolationPointsByCategory(
        int $employeeId,
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null
    ): array {
        $query = TeacherViolation::query()
            ->from('teacher_violations')
            ->join('teacher_violation_types', 'teacher_violations.violation_type_id', '=', 'teacher_violation_types.id')
            ->where('teacher_violations.institution_id', $institutionId)
            ->where('teacher_violations.employee_id', $employeeId)
            ->where('teacher_violations.status', TeacherViolation::STATUS_APPROVED)
            ->select(
                'teacher_violation_types.category',
                DB::raw('SUM(teacher_violations.point_value) as points'),
                DB::raw('COUNT(teacher_violations.id) as count')
            )
            ->groupBy('teacher_violation_types.category');

        $this->applyPeriodFilters($query, $academicYearId, $semesterId, 'teacher_violations');

        return $query->get()->map(fn ($row) => [
            'category' => $row->category ?: 'lainnya',
            'points' => (int) $row->points,
            'count' => (int) $row->count,
        ])->values()->all();
    }

    public function getPointSummary(
        int $employeeId,
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null
    ): array {
        $achievementPoints = $this->getAchievementPoints($employeeId, $institutionId, $academicYearId, $semesterId);
        $violationPoints = $this->getViolationPoints($employeeId, $institutionId, $academicYearId, $semesterId);
        $total = $achievementPoints - $violationPoints;
        $byCategory = $this->getPointsByCategory($employeeId, $institutionId, $academicYearId, $semesterId);
        $violationsByCategory = $this->getViolationPointsByCategory($employeeId, $institutionId, $academicYearId, $semesterId);

        $countQuery = TeacherAchievement::forInstitution($institutionId)
            ->forEmployee($employeeId)
            ->approved();
        $this->applyPeriodFilters($countQuery, $academicYearId, $semesterId);
        $approvedCount = (int) $countQuery->count();

        $vioCountQuery = TeacherViolation::forInstitution($institutionId)
            ->forEmployee($employeeId)
            ->approved();
        $this->applyPeriodFilters($vioCountQuery, $academicYearId, $semesterId);
        $violationCount = (int) $vioCountQuery->count();

        $pendingAchievement = (int) TeacherAchievement::forInstitution($institutionId)
            ->forEmployee($employeeId)
            ->pending()
            ->count();
        $pendingViolation = (int) TeacherViolation::forInstitution($institutionId)
            ->forEmployee($employeeId)
            ->pending()
            ->count();

        $matchedReward = $this->getMatchedReward($institutionId, $total);
        $unlockedRewards = $this->getUnlockedRewards($institutionId, $total);

        return [
            'employee_id' => $employeeId,
            'achievement_points' => $achievementPoints,
            'violation_points' => $violationPoints,
            'total_points' => $total,
            'approved_count' => $approvedCount,
            'violation_count' => $violationCount,
            'pending_count' => $pendingAchievement,
            'pending_violation_count' => $pendingViolation,
            'by_category' => $byCategory,
            'violations_by_category' => $violationsByCategory,
            'matched_reward' => $matchedReward ? $this->rewardToArray($matchedReward) : null,
            'unlocked_rewards' => $unlockedRewards->map(fn ($r) => $this->rewardToArray($r))->values()->all(),
            'academic_year_id' => $academicYearId,
            'semester_id' => $semesterId,
        ];
    }

    public function getMatchedReward(int $institutionId, int $totalPoint): ?TeacherPointReward
    {
        if ($totalPoint <= 0) {
            return null;
        }

        return TeacherPointReward::forInstitution($institutionId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('point_min')
            ->get()
            ->first(fn (TeacherPointReward $r) => $r->containsPoint($totalPoint));
    }

    /**
     * @return Collection<int, TeacherPointReward>
     */
    public function getUnlockedRewards(int $institutionId, int $totalPoint): Collection
    {
        return TeacherPointReward::forInstitution($institutionId)
            ->active()
            ->where('point_min', '<=', $totalPoint)
            ->orderBy('sort_order')
            ->orderBy('point_min')
            ->get();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listAchievementsForEmployee(
        int $employeeId,
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null,
        int $limit = 20,
        ?string $status = null
    ): Collection {
        $query = TeacherAchievement::with(['achievementType:id,name,category,point_value', 'giver:id,name', 'reviewer:id,name'])
            ->forInstitution($institutionId)
            ->forEmployee($employeeId)
            ->orderByDesc('achievement_date')
            ->orderByDesc('id');

        $this->applyPeriodFilters($query, $academicYearId, $semesterId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->limit($limit)->get()->map(fn (TeacherAchievement $a) => [
            'id' => $a->id,
            'title' => $a->title,
            'achievement_date' => $a->achievement_date?->format('Y-m-d'),
            'point_value' => $a->point_value,
            'level' => $a->level,
            'status' => $a->status,
            'notes' => $a->notes,
            'achievement_type' => $a->achievementType ? [
                'id' => $a->achievementType->id,
                'name' => $a->achievementType->name,
                'category' => $a->achievementType->category,
            ] : null,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listViolationsForEmployee(
        int $employeeId,
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null,
        int $limit = 20,
        ?string $status = null
    ): Collection {
        $query = TeacherViolation::with(['violationType:id,name,category,point_weight', 'reporter:id,name', 'reviewer:id,name'])
            ->forInstitution($institutionId)
            ->forEmployee($employeeId)
            ->orderByDesc('violation_date')
            ->orderByDesc('id');

        $this->applyPeriodFilters($query, $academicYearId, $semesterId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->limit($limit)->get()->map(fn (TeacherViolation $v) => [
            'id' => $v->id,
            'violation_date' => $v->violation_date?->format('Y-m-d'),
            'point_value' => $v->point_value,
            'status' => $v->status,
            'notes' => $v->notes,
            'sanction' => $v->sanction,
            'violation_type' => $v->violationType ? [
                'id' => $v->violationType->id,
                'name' => $v->violationType->name,
                'category' => $v->violationType->category,
            ] : null,
        ]);
    }

    public function listRewardLogsForEmployee(
        int $employeeId,
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null,
        int $limit = 20
    ): Collection {
        $query = TeacherRewardLog::with(['recorder:id,name', 'reward:id,reward_name'])
            ->forInstitution($institutionId)
            ->forEmployee($employeeId)
            ->orderByDesc('reward_date')
            ->orderByDesc('id');

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        return $query->limit($limit)->get()->map(fn (TeacherRewardLog $log) => [
            'id' => $log->id,
            'reward_name' => $log->reward_name,
            'reward_date' => $log->reward_date?->format('Y-m-d'),
            'score_at_reward' => $log->score_at_reward,
            'notes' => $log->notes,
            'recorder' => $log->recorder ? [
                'id' => $log->recorder->id,
                'name' => $log->recorder->name,
            ] : null,
        ]);
    }

    /**
     * Leaderboard by net points (prestasi − pelanggaran).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getLeaderboard(
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null,
        int $limit = 20
    ): Collection {
        $achSub = TeacherAchievement::query()
            ->select('employee_id', DB::raw('SUM(point_value) as achievement_points'), DB::raw('COUNT(*) as achievements_count'))
            ->where('institution_id', $institutionId)
            ->where('status', TeacherAchievement::STATUS_APPROVED);
        if ($academicYearId) {
            $achSub->where('academic_year_id', $academicYearId);
        }
        if ($semesterId) {
            $achSub->where('semester_id', $semesterId);
        }
        $achSub->groupBy('employee_id');

        $vioSub = TeacherViolation::query()
            ->select('employee_id', DB::raw('SUM(point_value) as violation_points'), DB::raw('COUNT(*) as violations_count'))
            ->where('institution_id', $institutionId)
            ->where('status', TeacherViolation::STATUS_APPROVED);
        if ($academicYearId) {
            $vioSub->where('academic_year_id', $academicYearId);
        }
        if ($semesterId) {
            $vioSub->where('semester_id', $semesterId);
        }
        $vioSub->groupBy('employee_id');

        $rows = Employee::query()
            ->from('employee')
            ->where('employee.institution_id', $institutionId)
            ->where('employee.status', 'Aktif')
            ->leftJoinSub($achSub, 'ach', 'ach.employee_id', '=', 'employee.id')
            ->leftJoinSub($vioSub, 'vio', 'vio.employee_id', '=', 'employee.id')
            ->where(function ($q) {
                $q->whereNotNull('ach.achievement_points')
                    ->orWhereNotNull('vio.violation_points');
            })
            ->select(
                'employee.id as employee_id',
                'employee.name',
                'employee.nip',
                'employee.type',
                'employee.subject',
                DB::raw('COALESCE(ach.achievement_points, 0) as achievement_points'),
                DB::raw('COALESCE(vio.violation_points, 0) as violation_points'),
                DB::raw('COALESCE(ach.achievement_points, 0) - COALESCE(vio.violation_points, 0) as total_points'),
                DB::raw('COALESCE(ach.achievements_count, 0) as achievements_count'),
                DB::raw('COALESCE(vio.violations_count, 0) as violations_count')
            )
            ->orderByDesc('total_points')
            ->orderByDesc('achievements_count')
            ->orderBy('employee.name')
            ->limit($limit)
            ->get();

        return $rows->values()->map(function ($row, $index) {
            return [
                'rank' => $index + 1,
                'employee_id' => (int) $row->employee_id,
                'employee' => [
                    'id' => (int) $row->employee_id,
                    'name' => $row->name,
                    'nip' => $row->nip,
                    'type' => $row->type,
                    'subject' => $row->subject,
                ],
                'achievement_points' => (int) $row->achievement_points,
                'violation_points' => (int) $row->violation_points,
                'total_points' => (int) $row->total_points,
                'achievements_count' => (int) $row->achievements_count,
                'violations_count' => (int) $row->violations_count,
            ];
        });
    }

    public function getReportSummary(
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null
    ): array {
        $baseAch = TeacherAchievement::forInstitution($institutionId)->approved();
        $this->applyPeriodFilters($baseAch, $academicYearId, $semesterId);

        $baseVio = TeacherViolation::forInstitution($institutionId)->approved();
        $this->applyPeriodFilters($baseVio, $academicYearId, $semesterId);

        $totalAchievements = (int) (clone $baseAch)->count();
        $achievementPoints = (int) (clone $baseAch)->sum('point_value');
        $totalViolations = (int) (clone $baseVio)->count();
        $violationPoints = (int) (clone $baseVio)->sum('point_value');
        $netPoints = $achievementPoints - $violationPoints;

        $uniqueTeachers = (int) DB::table('employee')
            ->where('institution_id', $institutionId)
            ->where(function ($q) use ($institutionId, $academicYearId, $semesterId) {
                $q->whereIn('id', function ($sub) use ($institutionId, $academicYearId, $semesterId) {
                    $sub->select('employee_id')->from('teacher_achievements')
                        ->where('institution_id', $institutionId)
                        ->where('status', TeacherAchievement::STATUS_APPROVED);
                    if ($academicYearId) {
                        $sub->where('academic_year_id', $academicYearId);
                    }
                    if ($semesterId) {
                        $sub->where('semester_id', $semesterId);
                    }
                })->orWhereIn('id', function ($sub) use ($institutionId, $academicYearId, $semesterId) {
                    $sub->select('employee_id')->from('teacher_violations')
                        ->where('institution_id', $institutionId)
                        ->where('status', TeacherViolation::STATUS_APPROVED);
                    if ($academicYearId) {
                        $sub->where('academic_year_id', $academicYearId);
                    }
                    if ($semesterId) {
                        $sub->where('semester_id', $semesterId);
                    }
                });
            })
            ->count();

        $pendingAchievement = (int) TeacherAchievement::forInstitution($institutionId)->pending()->count();
        $pendingViolation = (int) TeacherViolation::forInstitution($institutionId)->pending()->count();

        $byCategory = TeacherAchievement::query()
            ->from('teacher_achievements')
            ->join('teacher_achievement_types', 'teacher_achievements.achievement_type_id', '=', 'teacher_achievement_types.id')
            ->where('teacher_achievements.institution_id', $institutionId)
            ->where('teacher_achievements.status', TeacherAchievement::STATUS_APPROVED)
            ->select(
                'teacher_achievement_types.category',
                DB::raw('SUM(teacher_achievements.point_value) as points'),
                DB::raw('COUNT(teacher_achievements.id) as count')
            )
            ->groupBy('teacher_achievement_types.category');
        $this->applyPeriodFilters($byCategory, $academicYearId, $semesterId, 'teacher_achievements');

        $violationsByCategory = TeacherViolation::query()
            ->from('teacher_violations')
            ->join('teacher_violation_types', 'teacher_violations.violation_type_id', '=', 'teacher_violation_types.id')
            ->where('teacher_violations.institution_id', $institutionId)
            ->where('teacher_violations.status', TeacherViolation::STATUS_APPROVED)
            ->select(
                'teacher_violation_types.category',
                DB::raw('SUM(teacher_violations.point_value) as points'),
                DB::raw('COUNT(teacher_violations.id) as count')
            )
            ->groupBy('teacher_violation_types.category');
        $this->applyPeriodFilters($violationsByCategory, $academicYearId, $semesterId, 'teacher_violations');

        return [
            'total_achievements' => $totalAchievements,
            'achievement_points' => $achievementPoints,
            'total_violations' => $totalViolations,
            'violation_points' => $violationPoints,
            'total_points' => $netPoints,
            'unique_teachers' => $uniqueTeachers,
            'pending_count' => $pendingAchievement,
            'pending_violation_count' => $pendingViolation,
            'by_category' => $byCategory->get()->map(fn ($r) => [
                'category' => $r->category ?: 'lainnya',
                'points' => (int) $r->points,
                'count' => (int) $r->count,
            ])->values()->all(),
            'violations_by_category' => $violationsByCategory->get()->map(fn ($r) => [
                'category' => $r->category ?: 'lainnya',
                'points' => (int) $r->points,
                'count' => (int) $r->count,
            ])->values()->all(),
            'leaderboard' => $this->getLeaderboard($institutionId, $academicYearId, $semesterId, 10)->all(),
            'academic_year_id' => $academicYearId,
            'semester_id' => $semesterId,
        ];
    }

    public function ensureDefaultTypes(int $institutionId): void
    {
        if (TeacherAchievementType::forInstitution($institutionId)->exists()) {
            return;
        }

        $defaults = [
            ['code' => 'TP-AKD-01', 'name' => 'Pembimbing juara tingkat sekolah', 'category' => 'akademik', 'point_value' => 10, 'sort_order' => 1],
            ['code' => 'TP-AKD-02', 'name' => 'Pembimbing juara kabupaten', 'category' => 'akademik', 'point_value' => 25, 'sort_order' => 2],
            ['code' => 'TP-AKD-03', 'name' => 'Pembimbing juara provinsi', 'category' => 'akademik', 'point_value' => 40, 'sort_order' => 3],
            ['code' => 'TP-AKD-04', 'name' => 'Pembimbing juara nasional', 'category' => 'akademik', 'point_value' => 60, 'sort_order' => 4],
            ['code' => 'TP-PGB-01', 'name' => 'Mengikuti diklat/workshop', 'category' => 'pengembangan', 'point_value' => 5, 'sort_order' => 5],
            ['code' => 'TP-PGB-02', 'name' => 'Narasumber internal', 'category' => 'pengembangan', 'point_value' => 15, 'sort_order' => 6],
            ['code' => 'TP-PGD-01', 'name' => 'Pembina ekstrakurikuler aktif', 'category' => 'pengabdian', 'point_value' => 10, 'sort_order' => 7],
            ['code' => 'TP-PGD-02', 'name' => 'Panitia kegiatan sekolah', 'category' => 'pengabdian', 'point_value' => 8, 'sort_order' => 8],
            ['code' => 'TP-INV-01', 'name' => 'Inovasi media/metode pembelajaran', 'category' => 'inovasi', 'point_value' => 20, 'sort_order' => 9],
            ['code' => 'TP-INV-02', 'name' => 'Karya tulis / PTK', 'category' => 'inovasi', 'point_value' => 25, 'sort_order' => 10],
        ];

        foreach ($defaults as $row) {
            TeacherAchievementType::create([
                'institution_id' => $institutionId,
                'name' => $row['name'],
                'code' => $row['code'],
                'category' => $row['category'],
                'point_value' => $row['point_value'],
                'level_multipliers' => TeacherAchievementType::DEFAULT_MULTIPLIERS,
                'sort_order' => $row['sort_order'],
                'is_active' => true,
                'description' => null,
            ]);
        }
    }

    public function ensureDefaultRewards(int $institutionId): void
    {
        if (TeacherPointReward::forInstitution($institutionId)->exists()) {
            return;
        }

        $defaults = [
            ['point_min' => 50, 'point_max' => 99, 'reward_name' => 'Apresiasi lisan di rapat', 'description' => 'Disebutkan sebagai contoh baik dalam rapat guru.', 'sort_order' => 1],
            ['point_min' => 100, 'point_max' => 149, 'reward_name' => 'Piagam apresiasi sekolah', 'description' => 'Menerima piagam apresiasi dari kepala sekolah.', 'sort_order' => 2],
            ['point_min' => 150, 'point_max' => 9999, 'reward_name' => 'Prioritas usulan diklat', 'description' => 'Diprioritaskan untuk usulan diklat / penghargaan khusus.', 'sort_order' => 3],
        ];

        foreach ($defaults as $row) {
            TeacherPointReward::create(array_merge($row, [
                'institution_id' => $institutionId,
                'is_active' => true,
            ]));
        }
    }

    public function ensureDefaultViolationTypes(int $institutionId): void
    {
        if (TeacherViolationType::forInstitution($institutionId)->exists()) {
            return;
        }

        $defaults = [
            ['code' => 'TV-HDR-01', 'name' => 'Terlambat masuk kelas/rapat', 'category' => 'kehadiran', 'point_weight' => 5, 'sort_order' => 1],
            ['code' => 'TV-HDR-02', 'name' => 'Tidak hadir tanpa keterangan', 'category' => 'kehadiran', 'point_weight' => 15, 'sort_order' => 2],
            ['code' => 'TV-KDS-01', 'name' => 'Tidak memakai seragam sesuai aturan', 'category' => 'kedisiplinan', 'point_weight' => 5, 'sort_order' => 3],
            ['code' => 'TV-KDS-02', 'name' => 'Meninggalkan kelas tanpa izin', 'category' => 'kedisiplinan', 'point_weight' => 10, 'sort_order' => 4],
            ['code' => 'TV-ADM-01', 'name' => 'Tidak mengisi jurnal mengajar', 'category' => 'administrasi', 'point_weight' => 5, 'sort_order' => 5],
            ['code' => 'TV-ADM-02', 'name' => 'Terlambat input nilai', 'category' => 'administrasi', 'point_weight' => 8, 'sort_order' => 6],
        ];

        foreach ($defaults as $row) {
            TeacherViolationType::create([
                'institution_id' => $institutionId,
                'name' => $row['name'],
                'code' => $row['code'],
                'category' => $row['category'],
                'point_weight' => $row['point_weight'],
                'sort_order' => $row['sort_order'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * Guru piket mengajukan insiden guru (terlambat / kelas kosong) sebagai usulan poin minus ke KS.
     */
    public function proposeFromPiketIncident(
        PiketIncident $incident,
        array $data,
        User $reporter
    ): TeacherViolation {
        if ((int) $incident->institution_id !== (int) $reporter->institution_id
            && !$reporter->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'incident' => ['Insiden tidak berada di institusi Anda.'],
            ]);
        }

        if (!$incident->isTeacherRelated()) {
            throw ValidationException::withMessages([
                'incident_type' => ['Hanya insiden keterlambatan guru atau kelas kosong yang dapat diajukan ke Kepala Sekolah.'],
            ]);
        }

        $employeeId = $data['employee_id'] ?? $incident->employee_id;
        if (!$employeeId) {
            throw ValidationException::withMessages([
                'employee_id' => ['Insiden harus terkait guru untuk diajukan ke Kepala Sekolah.'],
            ]);
        }

        $employee = Employee::where('id', $employeeId)
            ->where('institution_id', $incident->institution_id)
            ->first();
        if (!$employee) {
            throw ValidationException::withMessages([
                'employee_id' => ['Guru tidak ditemukan di institusi ini.'],
            ]);
        }

        $existing = TeacherViolation::where('piket_incident_id', $incident->id)
            ->whereIn('status', [
                TeacherViolation::STATUS_PENDING,
                TeacherViolation::STATUS_APPROVED,
            ])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'piket_incident_id' => ['Insiden ini sudah diajukan ke Kepala Sekolah.'],
            ]);
        }

        // Hapus tautan usulan ditolak sebelumnya agar bisa diajukan ulang
        TeacherViolation::where('piket_incident_id', $incident->id)
            ->where('status', TeacherViolation::STATUS_REJECTED)
            ->update(['piket_incident_id' => null]);

        $this->ensureDefaultViolationTypes((int) $incident->institution_id);

        $type = TeacherViolationType::where('id', $data['violation_type_id'])
            ->where('institution_id', $incident->institution_id)
            ->where('is_active', true)
            ->firstOrFail();

        $notes = $data['notes'] ?? $incident->description;
        if ($incident->minutes_late && (!$notes || !str_contains((string) $notes, 'menit'))) {
            $lateNote = sprintf('Terlambat %d menit (laporan guru piket).', $incident->minutes_late);
            $notes = trim(($notes ? $notes.' ' : '').$lateNote);
        }
        if (!$notes) {
            $notes = match ($incident->incident_type) {
                PiketIncident::TYPE_TERLAMBAT_GURU => 'Keterlambatan guru dari laporan guru piket.',
                PiketIncident::TYPE_KELAS_KOSONG => 'Kelas kosong dari laporan guru piket.',
                default => 'Laporan guru piket.',
            };
        }

        $pointValue = array_key_exists('point_value', $data) && $data['point_value'] !== null
            ? (int) $data['point_value']
            : (int) $type->point_weight;

        [$defaultYear, $defaultSemester] = $this->resolveActivePeriod((int) $incident->institution_id);

        $violation = TeacherViolation::create([
            'institution_id' => $incident->institution_id,
            'employee_id' => $employee->id,
            'piket_incident_id' => $incident->id,
            'violation_type_id' => $type->id,
            'violation_date' => $data['violation_date'] ?? $incident->incident_date?->format('Y-m-d'),
            'point_value' => $pointValue,
            'notes' => $notes,
            'sanction' => $data['sanction'] ?? $type->default_sanction,
            'status' => TeacherViolation::STATUS_PENDING,
            'reported_by' => $reporter->id,
            'academic_year_id' => $data['academic_year_id'] ?? $defaultYear,
            'semester_id' => $data['semester_id'] ?? $defaultSemester,
        ]);

        if ($incident->status === PiketIncident::STATUS_OPEN) {
            $incident->update(['status' => PiketIncident::STATUS_CONFIRMED]);
        }

        return $violation->fresh([
            'employee',
            'violationType',
            'reporter',
            'piketIncident',
            'academicYear:id,name,code',
            'semester:id,name',
        ]);
    }

    /**
     * Selesaikan insiden piket terkait setelah KS menyetujui pelanggaran.
     */
    public function resolveLinkedPiketIncident(TeacherViolation $violation, User $reviewer): void
    {
        if (!$violation->piket_incident_id) {
            return;
        }

        PiketIncident::where('id', $violation->piket_incident_id)
            ->whereIn('status', [PiketIncident::STATUS_OPEN, PiketIncident::STATUS_CONFIRMED])
            ->update([
                'status' => PiketIncident::STATUS_RESOLVED,
                'resolved_by' => $reviewer->id,
                'resolved_at' => now(),
            ]);
    }

    /**
     * Kode jenis pelanggaran default berdasarkan tipe insiden piket.
     */
    public function suggestedViolationTypeCode(string $incidentType): ?string
    {
        return match ($incidentType) {
            PiketIncident::TYPE_TERLAMBAT_GURU => 'TV-HDR-01',
            PiketIncident::TYPE_KELAS_KOSONG => 'TV-HDR-02',
            default => null,
        };
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    public function resolvePeriodFromRequest($request, int $institutionId): array
    {
        $academicYearId = $request->filled('academic_year_id')
            ? (int) $request->academic_year_id
            : null;
        $semesterId = $request->filled('semester_id')
            ? (int) $request->semester_id
            : null;

        if ($request->has('academic_year_id') && ($request->academic_year_id === '' || $request->academic_year_id === 'all')) {
            $academicYearId = null;
        }
        if ($request->has('semester_id') && ($request->semester_id === '' || $request->semester_id === 'all')) {
            $semesterId = null;
        }

        if (!$request->has('academic_year_id') || !$request->has('semester_id')) {
            $institution = Institution::find($institutionId);
            if ($institution) {
                if (!$request->has('academic_year_id') && $institution->active_academic_year_id) {
                    $academicYearId = (int) $institution->active_academic_year_id;
                }
                if (!$request->has('semester_id') && $institution->active_semester_id) {
                    $semesterId = (int) $institution->active_semester_id;
                }
            }
        }

        return [$academicYearId, $semesterId];
    }

    public function resolveActivePeriod(int $institutionId): array
    {
        $institution = Institution::find($institutionId);

        return [
            $institution?->active_academic_year_id ? (int) $institution->active_academic_year_id : null,
            $institution?->active_semester_id ? (int) $institution->active_semester_id : null,
        ];
    }

    protected function applyPeriodFilters($query, ?int $academicYearId, ?int $semesterId, ?string $table = null): void
    {
        $yearCol = $table ? "{$table}.academic_year_id" : 'academic_year_id';
        $semCol = $table ? "{$table}.semester_id" : 'semester_id';

        if ($academicYearId) {
            $query->where($yearCol, $academicYearId);
        }
        if ($semesterId) {
            $query->where($semCol, $semesterId);
        }
    }

    protected function rewardToArray(TeacherPointReward $reward): array
    {
        return [
            'id' => $reward->id,
            'reward_name' => $reward->reward_name,
            'point_min' => $reward->point_min,
            'point_max' => $reward->point_max,
            'description' => $reward->description,
        ];
    }
}

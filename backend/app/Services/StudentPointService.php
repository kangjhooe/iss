<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\PointThreshold;
use App\Models\StudentActionLog;
use App\Models\Violation;
use Illuminate\Support\Collection;

class StudentPointService
{
    /**
     * Calculate violation points for a student, optionally scoped to academic year / semester.
     */
    public function getViolationPoints(int $studentId, int $institutionId, ?int $academicYearId = null, ?int $semesterId = null): int
    {
        $query = Violation::query()
            ->where('violations.institution_id', $institutionId)
            ->where('violations.student_id', $studentId)
            ->whereIn('violations.status', Violation::STATUSES_COUNTING_POINTS)
            ->join('violation_types', 'violations.violation_type_id', '=', 'violation_types.id');

        $this->applyPeriodFilters($query, 'violations', $academicYearId, $semesterId);

        return (int) $query->sum('violation_types.point_weight');
    }

    /**
     * Calculate achievement points for a student, optionally scoped to academic year / semester.
     */
    public function getAchievementPoints(int $studentId, int $institutionId, ?int $academicYearId = null, ?int $semesterId = null): int
    {
        $query = Achievement::forInstitution($institutionId)->forStudent($studentId);
        $this->applyPeriodFilters($query, null, $academicYearId, $semesterId);

        return (int) $query->sum('point_value');
    }

    /**
     * Total skor pelanggaran = violation points - achievement points.
     * Makin besar = makin buruk.
     */
    public function getTotalPoint(int $studentId, int $institutionId, ?int $academicYearId = null, ?int $semesterId = null): int
    {
        $violation = $this->getViolationPoints($studentId, $institutionId, $academicYearId, $semesterId);
        $achievement = $this->getAchievementPoints($studentId, $institutionId, $academicYearId, $semesterId);

        return $violation - $achievement;
    }

    /**
     * Point summary for a student within an optional period.
     */
    public function getPointSummary(int $studentId, int $institutionId, ?int $academicYearId = null, ?int $semesterId = null): array
    {
        $violationPts = $this->getViolationPoints($studentId, $institutionId, $academicYearId, $semesterId);
        $achievementPts = $this->getAchievementPoints($studentId, $institutionId, $academicYearId, $semesterId);
        $total = $violationPts - $achievementPts;

        return [
            'violation_points' => $violationPts,
            'achievement_points' => $achievementPts,
            'total_points' => $total,
            'achievement_bank' => $achievementPts,
            'academic_year_id' => $academicYearId,
            'semester_id' => $semesterId,
        ];
    }

    /**
     * Required threshold action for a given skor. Null if skor <= 0 or no matching rule.
     */
    public function getRequiredAction(int $institutionId, int $totalPoint): ?PointThreshold
    {
        if ($totalPoint <= 0) {
            return null;
        }

        return PointThreshold::forInstitution($institutionId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('point_min')
            ->get()
            ->first(fn (PointThreshold $t) => $t->containsPoint($totalPoint));
    }

    /**
     * Evaluate whether the student still needs action for the current threshold,
     * considering new violations / higher score after the last action.
     *
     * @return array{
     *   action_pending: bool,
     *   action_fulfilled: bool,
     *   reopen_reason: ?string,
     *   score_at_action: ?int,
     *   new_points_since_action: int,
     *   open_after_action_count: int,
     *   latest_action: ?StudentActionLog
     * }
     */
    public function evaluateActionCycle(
        int $studentId,
        int $institutionId,
        int $currentScore,
        ?PointThreshold $requiredAction,
        ?int $academicYearId = null,
        ?int $semesterId = null
    ): array {
        $empty = [
            'action_pending' => false,
            'action_fulfilled' => false,
            'reopen_reason' => null,
            'score_at_action' => null,
            'new_points_since_action' => 0,
            'open_after_action_count' => 0,
            'latest_action' => null,
        ];

        if (!$requiredAction) {
            if ($currentScore <= 0) {
                return $empty;
            }

            // Tanpa aturan: hanya action log manual (tanpa threshold)
            $latest = StudentActionLog::with('recorder:id,name')
                ->forInstitution($institutionId)
                ->forStudent($studentId)
                ->whereNull('point_threshold_id')
                ->orderByDesc('action_date')
                ->orderByDesc('id');

            if ($academicYearId) {
                $latest->where('academic_year_id', $academicYearId);
            }
            if ($semesterId) {
                $latest->where('semester_id', $semesterId);
            }
            $latest = $latest->first();

            if (!$latest) {
                return array_merge($empty, [
                    'action_pending' => true,
                    'reopen_reason' => 'no_threshold',
                ]);
            }

            return $this->buildCycleFromLatestAction(
                $studentId,
                $institutionId,
                $currentScore,
                $latest,
                $academicYearId,
                $semesterId,
                'no_threshold'
            );
        }

        $latest = $this->getLatestActionLog(
            $studentId,
            $institutionId,
            $academicYearId,
            $semesterId,
            $requiredAction->id
        );

        if (!$latest) {
            return array_merge($empty, [
                'action_pending' => true,
                'reopen_reason' => 'never_acted',
            ]);
        }

        return $this->buildCycleFromLatestAction(
            $studentId,
            $institutionId,
            $currentScore,
            $latest,
            $academicYearId,
            $semesterId
        );
    }

    /**
     * @return array{
     *   action_pending: bool,
     *   action_fulfilled: bool,
     *   reopen_reason: ?string,
     *   score_at_action: ?int,
     *   new_points_since_action: int,
     *   open_after_action_count: int,
     *   latest_action: ?StudentActionLog
     * }
     */
    protected function buildCycleFromLatestAction(
        int $studentId,
        int $institutionId,
        int $currentScore,
        StudentActionLog $latest,
        ?int $academicYearId,
        ?int $semesterId,
        ?string $defaultReason = null
    ): array {
        $scoreAtAction = $latest->score_at_action;
        $newPoints = $scoreAtAction !== null
            ? max(0, $currentScore - (int) $scoreAtAction)
            : 0;

        $openAfter = $this->countOpenViolationsAfterAction(
            $studentId,
            $institutionId,
            $latest,
            $academicYearId,
            $semesterId
        );

        $reopenReason = null;
        if ($scoreAtAction !== null && $currentScore > (int) $scoreAtAction) {
            $reopenReason = 'score_increased';
        } elseif ($openAfter > 0) {
            $reopenReason = 'new_violations';
        } elseif ($scoreAtAction === null && $this->hasViolationAfterActionDate(
            $studentId,
            $institutionId,
            $latest,
            $academicYearId,
            $semesterId
        )) {
            $reopenReason = 'new_violations';
        }

        $pending = $reopenReason !== null;

        return [
            'action_pending' => $pending,
            'action_fulfilled' => !$pending,
            'reopen_reason' => $reopenReason ?? ($pending ? $defaultReason : null),
            'score_at_action' => $scoreAtAction !== null ? (int) $scoreAtAction : null,
            'new_points_since_action' => $newPoints,
            'open_after_action_count' => $openAfter,
            'latest_action' => $latest,
        ];
    }

    /**
     * Violations in period for a student (for UI detail).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listViolationsForStudent(
        int $studentId,
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null,
        int $limit = 20
    ): Collection {
        $query = Violation::with('violationType:id,name,category,point_weight')
            ->where('institution_id', $institutionId)
            ->where('student_id', $studentId)
            ->whereIn('status', Violation::STATUSES_COUNTING_POINTS)
            ->orderByDesc('violation_date')
            ->orderByDesc('id');

        $this->applyPeriodFilters($query, null, $academicYearId, $semesterId);

        return $query->limit($limit)->get()->map(fn (Violation $v) => [
            'id' => $v->id,
            'violation_date' => $v->violation_date?->format('Y-m-d'),
            'status' => $v->status,
            'sanction' => $v->sanction,
            'description' => $v->description,
            'point_weight' => (int) ($v->violationType?->point_weight ?? 0),
            'violation_type' => $v->violationType ? [
                'id' => $v->violationType->id,
                'name' => $v->violationType->name,
                'category' => $v->violationType->category,
                'point_weight' => (int) $v->violationType->point_weight,
            ] : null,
        ]);
    }

    /**
     * Achievements in period for a student (for UI detail).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listAchievementsForStudent(
        int $studentId,
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null,
        int $limit = 20
    ): Collection {
        $query = Achievement::with('achievementType:id,name,point_value,category')
            ->where('institution_id', $institutionId)
            ->where('student_id', $studentId)
            ->orderByDesc('achievement_date')
            ->orderByDesc('id');

        $this->applyPeriodFilters($query, null, $academicYearId, $semesterId);

        return $query->limit($limit)->get()->map(fn (Achievement $a) => [
            'id' => $a->id,
            'achievement_date' => $a->achievement_date?->format('Y-m-d'),
            'point_value' => (int) $a->point_value,
            'notes' => $a->notes,
            'achievement_type' => $a->achievementType ? [
                'id' => $a->achievementType->id,
                'name' => $a->achievementType->name,
                'point_value' => (int) $a->achievementType->point_value,
                'category' => $a->achievementType->category,
            ] : null,
        ]);
    }

    /**
     * Count open violations that occurred after the last action (need follow-up).
     */
    public function countOpenViolationsAfterAction(
        int $studentId,
        int $institutionId,
        StudentActionLog $action,
        ?int $academicYearId = null,
        ?int $semesterId = null
    ): int {
        $actionDate = $action->action_date?->format('Y-m-d');
        $actionCreated = $action->created_at;

        $query = Violation::where('institution_id', $institutionId)
            ->where('student_id', $studentId)
            ->whereIn('status', ['dicatat', 'sanksi_diberikan']);

        $this->applyPeriodFilters($query, null, $academicYearId, $semesterId);

        if ($actionDate) {
            $query->where(function ($q) use ($actionDate, $actionCreated) {
                $q->whereDate('violation_date', '>', $actionDate);
                if ($actionCreated) {
                    $q->orWhere(function ($q2) use ($actionDate, $actionCreated) {
                        $q2->whereDate('violation_date', '=', $actionDate)
                            ->where('created_at', '>', $actionCreated);
                    });
                }
            });
        }

        return $query->count();
    }

    /**
     * Any violation (any status) after action date — used for legacy logs.
     */
    protected function hasViolationAfterActionDate(
        int $studentId,
        int $institutionId,
        StudentActionLog $action,
        ?int $academicYearId = null,
        ?int $semesterId = null
    ): bool {
        $actionDate = $action->action_date?->format('Y-m-d');
        if (!$actionDate) {
            return false;
        }

        $query = Violation::where('institution_id', $institutionId)
            ->where('student_id', $studentId)
            ->whereIn('status', Violation::STATUSES_COUNTING_POINTS)
            ->whereDate('violation_date', '>', $actionDate);

        $this->applyPeriodFilters($query, null, $academicYearId, $semesterId);

        return $query->exists();
    }

    /**
     * @deprecated Prefer evaluateActionCycle(). Kept for compatibility.
     */
    public function hasFulfilledAction(
        int $studentId,
        int $institutionId,
        int $pointThresholdId,
        ?int $academicYearId = null,
        ?int $semesterId = null
    ): bool {
        $threshold = PointThreshold::find($pointThresholdId);
        if (!$threshold) {
            return false;
        }
        $score = $this->getTotalPoint($studentId, $institutionId, $academicYearId, $semesterId);
        $cycle = $this->evaluateActionCycle(
            $studentId,
            $institutionId,
            $score,
            $threshold,
            $academicYearId,
            $semesterId
        );

        return $cycle['action_fulfilled'];
    }

    /**
     * Latest action log for student in period (optional filter by threshold).
     */
    public function getLatestActionLog(
        int $studentId,
        int $institutionId,
        ?int $academicYearId = null,
        ?int $semesterId = null,
        ?int $pointThresholdId = null
    ): ?StudentActionLog {
        $query = StudentActionLog::with('recorder:id,name')
            ->forInstitution($institutionId)
            ->forStudent($studentId)
            ->orderByDesc('action_date')
            ->orderByDesc('id');

        $this->applyPeriodFilters($query, null, $academicYearId, $semesterId);

        if ($pointThresholdId) {
            $query->where('point_threshold_id', $pointThresholdId);
        }

        return $query->first();
    }

    /**
     * Apply academic year / semester filters to a query.
     */
    protected function applyPeriodFilters($query, ?string $table, ?int $academicYearId, ?int $semesterId): void
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
}

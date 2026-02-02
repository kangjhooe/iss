<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\PointThreshold;
use App\Models\Student;
use App\Models\Violation;

class StudentPointService
{
    /**
     * Calculate violation points (negative). Sum of violation_type.point_weight from all violations.
     */
    public function getViolationPoints(int $studentId, int $institutionId): int
    {
        return (int) Violation::forInstitution($institutionId)
            ->forStudent($studentId)
            ->join('violation_types', 'violations.violation_type_id', '=', 'violation_types.id')
            ->where('violation_types.institution_id', $institutionId)
            ->sum('violation_types.point_weight');
    }

    /**
     * Calculate achievement points (positive). Sum of point_value from all achievements.
     */
    public function getAchievementPoints(int $studentId, int $institutionId): int
    {
        return (int) Achievement::forInstitution($institutionId)
            ->forStudent($studentId)
            ->sum('point_value');
    }

    /**
     * Total point (skor pelanggaran) = violation points - achievement points.
     * Makin besar = makin buruk (banyak pelanggaran). Makin kecil/negatif = makin baik (banyak prestasi).
     */
    public function getTotalPoint(int $studentId, int $institutionId): int
    {
        $violation = $this->getViolationPoints($studentId, $institutionId);
        $achievement = $this->getAchievementPoints($studentId, $institutionId);
        return $violation - $achievement;
    }

    /**
     * Get point summary for a student: violation_pts, achievement_pts, total_pts (skor pelanggaran), achievement_bank (tabung prestasi).
     */
    public function getPointSummary(int $studentId, int $institutionId): array
    {
        $violationPts = $this->getViolationPoints($studentId, $institutionId);
        $achievementPts = $this->getAchievementPoints($studentId, $institutionId);
        $total = $violationPts - $achievementPts; // skor pelanggaran: makin besar makin buruk
        return [
            'violation_points' => $violationPts,
            'achievement_points' => $achievementPts,
            'total_points' => $total,
            'achievement_bank' => $achievementPts, // tabung prestasi (poin yang ditabung dari prestasi)
        ];
    }

    /**
     * Get the required action (threshold) for a given total point (skor pelanggaran).
     * Thresholds are checked by sort_order ascending; first match wins.
     * Convention: high total = bad; e.g. point_min=40, point_max=999 => "Panggilan orang tua".
     */
    public function getRequiredAction(int $institutionId, int $totalPoint): ?PointThreshold
    {
        return PointThreshold::forInstitution($institutionId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('point_min')
            ->get()
            ->first(fn (PointThreshold $t) => $t->containsPoint($totalPoint));
    }
}

<?php

namespace App\Services;

use App\Models\Correspondence;
use App\Models\CorrespondenceDisposition;
use Illuminate\Support\Facades\DB;

class CorrespondenceStatisticsService
{
    /**
     * Get statistics for correspondence dashboard.
     */
    public function getStatistics(?int $institutionId = null, ?string $year = null, ?string $month = null): array
    {
        $query = Correspondence::query();

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        if ($year) {
            $query->whereYear('date', $year);
        }

        if ($month) {
            $query->whereMonth('date', $month);
        }

        // Total counts by type
        $totalByType = (clone $query)
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        // Total counts by status
        $totalByStatus = (clone $query)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Total counts by priority
        $totalByPriority = (clone $query)
            ->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority')
            ->toArray();

        // Monthly trend (last 12 months)
        $monthlyTrend = (clone $query)
            ->select(
                DB::raw('YEAR(date) as year'),
                DB::raw('MONTH(date) as month'),
                DB::raw('type'),
                DB::raw('count(*) as total')
            )
            ->where('date', '>=', now()->subMonths(12))
            ->groupBy('year', 'month', 'type')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->groupBy(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            })
            ->map(function ($group) {
                return $group->pluck('total', 'type')->toArray();
            })
            ->toArray();

        // Pending approvals
        $pendingApprovals = (clone $query)
            ->where('status', 'pending')
            ->count();

        // Pending dispositions
        $pendingDispositions = CorrespondenceDisposition::query()
            ->whereHas('correspondence', function ($q) use ($institutionId) {
                if ($institutionId) {
                    $q->where('institution_id', $institutionId);
                }
            })
            ->where('status', 'pending')
            ->count();

        // Recent correspondence (last 7 days)
        $recentCount = (clone $query)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // By letter type code
        $byLetterType = (clone $query)
            ->select('letter_type_code', DB::raw('count(*) as total'))
            ->whereNotNull('letter_type_code')
            ->groupBy('letter_type_code')
            ->pluck('total', 'letter_type_code')
            ->toArray();

        return [
            'summary' => [
                'total' => (clone $query)->count(),
                'masuk' => $totalByType['masuk'] ?? 0,
                'keluar' => $totalByType['keluar'] ?? 0,
                'internal' => $totalByType['internal'] ?? 0,
                'recent' => $recentCount,
            ],
            'by_status' => [
                'draft' => $totalByStatus['draft'] ?? 0,
                'pending' => $totalByStatus['pending'] ?? 0,
                'approved' => $totalByStatus['approved'] ?? 0,
                'sent' => $totalByStatus['sent'] ?? 0,
                'archived' => $totalByStatus['archived'] ?? 0,
            ],
            'by_priority' => [
                'biasa' => $totalByPriority['biasa'] ?? 0,
                'penting' => $totalByPriority['penting'] ?? 0,
                'sangat_penting' => $totalByPriority['sangat_penting'] ?? 0,
            ],
            'by_letter_type' => $byLetterType,
            'monthly_trend' => $monthlyTrend,
            'pending' => [
                'approvals' => $pendingApprovals,
                'dispositions' => $pendingDispositions,
            ],
        ];
    }
}

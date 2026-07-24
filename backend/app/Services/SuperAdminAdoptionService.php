<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SuperAdminAdoptionService
{
    /**
     * Map permission keys to auditable model class names used in audit_logs.
     *
     * @var array<string, list<string>>
     */
    private const MODULE_AUDIT_TYPES = [
        'institution' => [
            \App\Models\Institution::class,
        ],
        'student' => [
            \App\Models\Student::class,
            \App\Models\StudentMutation::class,
        ],
        'teacher' => [
            \App\Models\Employee::class,
            \App\Models\Teacher::class,
            \App\Models\TeacherMutation::class,
        ],
        'facility' => [
            \App\Models\Building::class,
            \App\Models\Room::class,
            \App\Models\Land::class,
            \App\Models\LabBooking::class,
            \App\Models\LabUsageJournal::class,
        ],
        'inventory' => [
            \App\Models\InventoryItem::class,
            \App\Models\InventoryCategory::class,
            \App\Models\InventoryTransaction::class,
            \App\Models\InventoryLoan::class,
            \App\Models\InventoryMaintenance::class,
        ],
        'class' => [
            \App\Models\SchoolClass::class,
            \App\Models\Subject::class,
        ],
        'correspondence' => [
            \App\Models\Correspondence::class,
        ],
        'violation' => [
            \App\Models\Violation::class,
        ],
        'teaching_journal' => [
            \App\Models\TeachingJournal::class,
        ],
        'digital_archive' => [
            \App\Models\DigitalArchive::class,
        ],
        'grade_book' => [
            \App\Models\Grade::class,
        ],
        'attendance' => [
            \App\Models\StudentAttendance::class,
            \App\Models\EmployeeAttendance::class,
        ],
        'schedule' => [
            \App\Models\LessonSchedule::class,
        ],
        'guest_book' => [
            \App\Models\GuestVisit::class,
        ],
        'document_pickup' => [
            \App\Models\DocumentPickup::class,
        ],
        'extracurricular' => [
            \App\Models\Extracurricular::class,
        ],
        'library' => [
            \App\Models\LibraryBook::class,
        ],
        'academic_calendar' => [
            \App\Models\AcademicCalendarEvent::class,
            \App\Models\AcademicYear::class,
            \App\Models\Semester::class,
        ],
        'ppdb' => [
            \App\Models\PpdbApplicant::class,
            \App\Models\PpdbPeriod::class,
            \App\Models\PpdbChannel::class,
        ],
        'online_exam' => [
            \App\Models\Exam::class,
            \App\Models\QuestionBank::class,
            \App\Models\BankSoal::class,
        ],
        'teacher_appreciation' => [
            \App\Models\TeacherAchievement::class,
            \App\Models\TeacherViolation::class,
        ],
    ];

    /**
     * @return array<string, mixed>
     */
    public function build(int $inactiveDays = 30, int $usageDays = 30): array
    {
        $inactiveDays = max(1, min($inactiveDays, 365));
        $usageDays = max(7, min($usageDays, 90));

        $now = now();
        $inactiveCutoff = $now->copy()->subDays($inactiveDays);
        $usageFrom = $now->copy()->subDays($usageDays)->startOfDay();
        $prevFrom = $usageFrom->copy()->subDays($usageDays);
        $prevTo = $usageFrom->copy()->subSecond();

        $institutions = $this->buildInstitutions($inactiveCutoff, $usageFrom, $prevFrom, $prevTo, $usageDays);
        $modules = $this->buildModules($usageFrom);
        $usage = $this->buildUsage($usageFrom, $usageDays, $institutions);
        $churn = $this->buildChurn($institutions, $usageFrom, $usageDays, $inactiveDays);

        $inactiveCount = $institutions->where('is_inactive', true)->where('is_active', true)->count();
        $noAdminCount = $institutions->where('admins_count', 0)->where('is_active', true)->count();
        $atRiskCount = $institutions->whereIn('churn_risk', ['high', 'medium'])->where('is_active', true)->count();

        return [
            'summary' => [
                'institutions' => $institutions->count(),
                'active_institutions' => $institutions->where('is_active', true)->count(),
                'inactive_activity_count' => $inactiveCount,
                'no_admin_count' => $noAdminCount,
                'at_risk_count' => $atRiskCount,
                'churned_count' => $institutions->where('is_active', false)->count(),
                'inactive_days_threshold' => $inactiveDays,
                'usage_days' => $usageDays,
                'total_students' => (int) Student::where('status', 'Aktif')->count(),
                'total_teachers' => (int) Employee::where('type', 'Guru')->where('status', 'Aktif')->count(),
                'events_period' => (int) $usage['totals']['events'],
                'active_institutions_period' => (int) $usage['totals']['active_institutions'],
            ],
            'modules' => $modules,
            'usage' => $usage,
            'churn' => $churn,
            'institutions' => $institutions->values(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildInstitutions(
        Carbon $inactiveCutoff,
        Carbon $usageFrom,
        Carbon $prevFrom,
        Carbon $prevTo,
        int $usageDays
    ): Collection {
        $lastActivity = AuditLog::query()
            ->select('institution_id', DB::raw('MAX(created_at) as last_activity_at'))
            ->whereNotNull('institution_id')
            ->groupBy('institution_id');

        $currentEvents = AuditLog::query()
            ->select('institution_id', DB::raw('COUNT(*) as events_count'))
            ->whereNotNull('institution_id')
            ->where('created_at', '>=', $usageFrom)
            ->groupBy('institution_id');

        $previousEvents = AuditLog::query()
            ->select('institution_id', DB::raw('COUNT(*) as events_count'))
            ->whereNotNull('institution_id')
            ->whereBetween('created_at', [$prevFrom, $prevTo])
            ->groupBy('institution_id');

        $modulesUsed = AuditLog::query()
            ->select('institution_id', DB::raw('COUNT(DISTINCT auditable_type) as modules_used'))
            ->whereNotNull('institution_id')
            ->where('created_at', '>=', $usageFrom)
            ->groupBy('institution_id');

        return Institution::query()
            ->leftJoinSub($lastActivity, 'la', fn ($join) => $join->on('institution.id', '=', 'la.institution_id'))
            ->leftJoinSub($currentEvents, 'ce', fn ($join) => $join->on('institution.id', '=', 'ce.institution_id'))
            ->leftJoinSub($previousEvents, 'pe', fn ($join) => $join->on('institution.id', '=', 'pe.institution_id'))
            ->leftJoinSub($modulesUsed, 'mu', fn ($join) => $join->on('institution.id', '=', 'mu.institution_id'))
            ->select([
                'institution.id',
                'institution.name',
                'institution.npsn',
                'institution.level',
                'institution.type',
                'institution.is_active',
                'institution.created_at',
                'institution.updated_at',
                'la.last_activity_at',
                'ce.events_count as events_current',
                'pe.events_count as events_previous',
                'mu.modules_used',
            ])
            ->withCount([
                'students as active_students_count' => fn ($q) => $q->where('status', 'Aktif'),
                'teachers as active_teachers_count' => fn ($q) => $q->where('status', 'Aktif'),
                'users as admins_count' => fn ($q) => $q->where('role', 'institution_admin')->where(function ($q2) {
                    $q2->where('is_active', true)->orWhereNull('is_active');
                }),
                'users as users_count',
            ])
            ->orderByRaw('la.last_activity_at IS NULL DESC')
            ->orderBy('la.last_activity_at', 'asc')
            ->get()
            ->map(function ($inst) use ($inactiveCutoff, $usageDays) {
                $lastAt = $inst->last_activity_at;
                $eventsCurrent = (int) ($inst->events_current ?? 0);
                $eventsPrevious = (int) ($inst->events_previous ?? 0);
                $changePct = $this->changePct($eventsCurrent, $eventsPrevious);
                $isInactive = !$lastAt || Carbon::parse($lastAt)->lt($inactiveCutoff);
                $churnRisk = $this->resolveChurnRisk(
                    (bool) $inst->is_active,
                    $isInactive,
                    (int) $inst->admins_count,
                    $eventsCurrent,
                    $changePct,
                    $inst->created_at
                );

                return [
                    'id' => $inst->id,
                    'name' => $inst->name,
                    'npsn' => $inst->npsn,
                    'level' => $inst->level,
                    'type' => $inst->type,
                    'is_active' => (bool) $inst->is_active,
                    'active_students_count' => (int) $inst->active_students_count,
                    'active_teachers_count' => (int) $inst->active_teachers_count,
                    'admins_count' => (int) $inst->admins_count,
                    'users_count' => (int) $inst->users_count,
                    'last_activity_at' => $lastAt ? Carbon::parse($lastAt)->toIso8601String() : null,
                    'is_inactive' => $isInactive,
                    'events_current' => $eventsCurrent,
                    'events_previous' => $eventsPrevious,
                    'events_change_pct' => $changePct,
                    'modules_used' => (int) ($inst->modules_used ?? 0),
                    'avg_events_per_day' => round($eventsCurrent / max(1, $usageDays), 1),
                    'churn_risk' => $churnRisk,
                    'created_at' => $inst->created_at?->toIso8601String(),
                    'updated_at' => $inst->updated_at?->toIso8601String(),
                ];
            });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildModules(Carbon $usageFrom): array
    {
        $totalInstitutions = max(1, Institution::where('is_active', true)->count());

        $accessAdoption = DB::table('user_permissions')
            ->join('user', 'user.id', '=', 'user_permissions.user_id')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->whereNotNull('user.institution_id')
            ->whereIn('user.role', ['teacher', 'staff'])
            ->select(
                'permissions.key',
                'permissions.label',
                DB::raw('COUNT(DISTINCT user.institution_id) as institutions_count'),
                DB::raw('COUNT(DISTINCT user.id) as users_count')
            )
            ->groupBy('permissions.id', 'permissions.key', 'permissions.label')
            ->get()
            ->keyBy('key');

        $typeToKey = [];
        foreach (self::MODULE_AUDIT_TYPES as $key => $classes) {
            foreach ($classes as $class) {
                $typeToKey[$class] = $key;
                $typeToKey[class_basename($class)] = $key;
            }
        }

        $usageRows = AuditLog::query()
            ->select('institution_id', 'auditable_type', DB::raw('COUNT(*) as events_count'))
            ->whereNotNull('institution_id')
            ->where('created_at', '>=', $usageFrom)
            ->groupBy('institution_id', 'auditable_type')
            ->get();

        $usageByKey = [];
        foreach ($usageRows as $row) {
            $key = $typeToKey[$row->auditable_type]
                ?? $typeToKey[class_basename((string) $row->auditable_type)]
                ?? null;
            if (!$key) {
                continue;
            }
            if (!isset($usageByKey[$key])) {
                $usageByKey[$key] = [
                    'institutions' => [],
                    'events' => 0,
                ];
            }
            $usageByKey[$key]['institutions'][$row->institution_id] = true;
            $usageByKey[$key]['events'] += (int) $row->events_count;
        }

        return Permission::orderBy('label')->get(['key', 'label'])->map(function ($p) use (
            $accessAdoption,
            $usageByKey,
            $totalInstitutions
        ) {
            $access = $accessAdoption->get($p->key);
            $accessCount = (int) ($access->institutions_count ?? 0);
            $usage = $usageByKey[$p->key] ?? ['institutions' => [], 'events' => 0];
            $usageCount = count($usage['institutions']);

            return [
                'key' => $p->key,
                'label' => $p->label,
                'institutions_count' => $accessCount,
                'users_count' => (int) ($access->users_count ?? 0),
                'adoption_pct' => round(($accessCount / $totalInstitutions) * 100, 1),
                'usage_institutions_count' => $usageCount,
                'usage_pct' => round(($usageCount / $totalInstitutions) * 100, 1),
                'events_count' => (int) $usage['events'],
                'has_usage_tracking' => array_key_exists($p->key, self::MODULE_AUDIT_TYPES),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $institutions
     * @return array<string, mixed>
     */
    private function buildUsage(Carbon $usageFrom, int $usageDays, Collection $institutions): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : 'DATE(created_at)';

        $daily = AuditLog::query()
            ->select(
                DB::raw("{$dateExpr} as day"),
                DB::raw('COUNT(*) as events'),
                DB::raw('COUNT(DISTINCT institution_id) as institutions'),
                DB::raw('COUNT(DISTINCT user_id) as users')
            )
            ->whereNotNull('institution_id')
            ->where('created_at', '>=', $usageFrom)
            ->groupBy(DB::raw($dateExpr))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $trend = [];
        for ($i = $usageDays - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $row = $daily->get($day);
            $trend[] = [
                'date' => $day,
                'events' => (int) ($row->events ?? 0),
                'institutions' => (int) ($row->institutions ?? 0),
                'users' => (int) ($row->users ?? 0),
            ];
        }

        $byModule = collect($this->topUsageModules($usageFrom, 12));

        $totalEvents = (int) array_sum(array_column($trend, 'events'));
        $activeInstPeriod = $institutions->where('events_current', '>', 0)->count();
        $avgDailyEvents = round($totalEvents / max(1, $usageDays), 1);
        $avgDailyInstitutions = round(
            array_sum(array_column($trend, 'institutions')) / max(1, $usageDays),
            1
        );

        return [
            'days' => $usageDays,
            'trend' => $trend,
            'by_module' => $byModule,
            'totals' => [
                'events' => $totalEvents,
                'active_institutions' => $activeInstPeriod,
                'avg_daily_events' => $avgDailyEvents,
                'avg_daily_institutions' => $avgDailyInstitutions,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topUsageModules(Carbon $usageFrom, int $limit = 12): array
    {
        $typeToKey = [];
        $labels = Permission::pluck('label', 'key');
        foreach (self::MODULE_AUDIT_TYPES as $key => $classes) {
            foreach ($classes as $class) {
                $typeToKey[$class] = $key;
                $typeToKey[class_basename($class)] = $key;
            }
        }

        $rows = AuditLog::query()
            ->select('auditable_type', DB::raw('COUNT(*) as events_count'), DB::raw('COUNT(DISTINCT institution_id) as institutions_count'))
            ->whereNotNull('institution_id')
            ->where('created_at', '>=', $usageFrom)
            ->groupBy('auditable_type')
            ->get();

        $aggregated = [];
        foreach ($rows as $row) {
            $key = $typeToKey[$row->auditable_type]
                ?? $typeToKey[class_basename((string) $row->auditable_type)]
                ?? ('other:' . class_basename((string) $row->auditable_type));

            if (!isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'key' => $key,
                    'label' => $labels[$key] ?? class_basename((string) $row->auditable_type),
                    'events_count' => 0,
                    'institution_ids' => [],
                ];
            }
            $aggregated[$key]['events_count'] += (int) $row->events_count;
        }

        // Re-count distinct institutions per mapped module key
        $instRows = AuditLog::query()
            ->select('auditable_type', 'institution_id')
            ->whereNotNull('institution_id')
            ->where('created_at', '>=', $usageFrom)
            ->groupBy('auditable_type', 'institution_id')
            ->get();

        foreach ($instRows as $row) {
            $key = $typeToKey[$row->auditable_type]
                ?? $typeToKey[class_basename((string) $row->auditable_type)]
                ?? ('other:' . class_basename((string) $row->auditable_type));
            if (!isset($aggregated[$key])) {
                continue;
            }
            $aggregated[$key]['institution_ids'][$row->institution_id] = true;
        }

        return collect($aggregated)
            ->map(function (array $row) {
                return [
                    'key' => $row['key'],
                    'label' => $row['label'],
                    'events_count' => $row['events_count'],
                    'institutions_count' => count($row['institution_ids']),
                ];
            })
            ->sortByDesc('events_count')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $institutions
     * @return array<string, mixed>
     */
    private function buildChurn(
        Collection $institutions,
        Carbon $usageFrom,
        int $usageDays,
        int $inactiveDays
    ): array {
        $churned = $institutions->where('is_active', false)->values();
        $recentlyChurned = $churned
            ->filter(function ($inst) use ($usageFrom) {
                if (empty($inst['updated_at'])) {
                    return false;
                }

                return Carbon::parse($inst['updated_at'])->gte($usageFrom);
            })
            ->values();

        $newInstitutions = $institutions
            ->filter(function ($inst) use ($usageFrom) {
                return !empty($inst['created_at']) && Carbon::parse($inst['created_at'])->gte($usageFrom);
            })
            ->values();

        $activeAtRisk = $institutions
            ->where('is_active', true)
            ->whereIn('churn_risk', ['high', 'medium'])
            ->sortBy(fn ($i) => $i['churn_risk'] === 'high' ? 0 : 1)
            ->values();

        $declining = $institutions
            ->where('is_active', true)
            ->filter(fn ($i) => ($i['events_change_pct'] ?? 0) <= -40 && ($i['events_previous'] ?? 0) > 0)
            ->sortBy('events_change_pct')
            ->values();

        $activeBase = max(1, $institutions->where('is_active', true)->count() + $recentlyChurned->count());
        $churnRate = round(($recentlyChurned->count() / $activeBase) * 100, 1);

        $riskBreakdown = [
            'high' => $institutions->where('is_active', true)->where('churn_risk', 'high')->count(),
            'medium' => $institutions->where('is_active', true)->where('churn_risk', 'medium')->count(),
            'low' => $institutions->where('is_active', true)->where('churn_risk', 'low')->count(),
            'churned' => $churned->count(),
        ];

        return [
            'period_days' => $usageDays,
            'inactive_days_threshold' => $inactiveDays,
            'churn_rate_pct' => $churnRate,
            'recently_churned_count' => $recentlyChurned->count(),
            'new_institutions_count' => $newInstitutions->count(),
            'net_institutions' => $newInstitutions->count() - $recentlyChurned->count(),
            'at_risk_count' => $activeAtRisk->count(),
            'declining_count' => $declining->count(),
            'risk_breakdown' => $riskBreakdown,
            'at_risk' => $activeAtRisk->take(20)->values(),
            'recently_churned' => $recentlyChurned->take(20)->values(),
            'declining' => $declining->take(20)->values(),
            'new_institutions' => $newInstitutions->take(20)->values(),
        ];
    }

    private function changePct(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function resolveChurnRisk(
        bool $isActive,
        bool $isInactive,
        int $adminsCount,
        int $eventsCurrent,
        ?float $changePct,
        mixed $createdAt
    ): string {
        if (!$isActive) {
            return 'churned';
        }

        $isNew = $createdAt && Carbon::parse($createdAt)->gte(now()->subDays(14));
        if ($isNew && $eventsCurrent === 0) {
            return 'medium';
        }

        if ($isInactive || ($eventsCurrent === 0 && !$isNew) || ($changePct !== null && $changePct <= -70)) {
            return 'high';
        }

        if ($adminsCount === 0 || ($changePct !== null && $changePct <= -40)) {
            return 'medium';
        }

        return 'low';
    }
}

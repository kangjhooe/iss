<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SuperAdminAdoptionController extends Controller
{
    private function ensureSuperAdmin(Request $request): void
    {
        if (!$request->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Module adoption & activity monitoring across institutions.
     */
    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        try {
            $inactiveDays = max(1, min((int) $request->get('inactive_days', 30), 365));
            $cutoff = now()->subDays($inactiveDays);

            $lastActivity = AuditLog::query()
                ->select('institution_id', DB::raw('MAX(created_at) as last_activity_at'))
                ->whereNotNull('institution_id')
                ->groupBy('institution_id');

            $institutions = Institution::query()
                ->leftJoinSub($lastActivity, 'la', function ($join) {
                    $join->on('institution.id', '=', 'la.institution_id');
                })
                ->select([
                    'institution.id',
                    'institution.name',
                    'institution.npsn',
                    'institution.level',
                    'institution.type',
                    'institution.is_active',
                    'institution.created_at',
                    'la.last_activity_at',
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
                ->map(function ($inst) use ($cutoff) {
                    $lastAt = $inst->last_activity_at;
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
                        'last_activity_at' => $lastAt ? \Carbon\Carbon::parse($lastAt)->toIso8601String() : null,
                        'is_inactive' => !$lastAt || \Carbon\Carbon::parse($lastAt)->lt($cutoff),
                        'created_at' => $inst->created_at?->toIso8601String(),
                    ];
                });

            // Module adoption: institutions with ≥1 teacher/staff holding each permission
            $moduleAdoption = DB::table('user_permissions')
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
                ->orderByDesc('institutions_count')
                ->get();

            $allPermissions = Permission::orderBy('label')->get(['key', 'label']);
            $adoptionMap = $moduleAdoption->keyBy('key');
            $totalInstitutions = max(1, Institution::where('is_active', true)->count());

            $modules = $allPermissions->map(function ($p) use ($adoptionMap, $totalInstitutions) {
                $row = $adoptionMap->get($p->key);
                $count = (int) ($row->institutions_count ?? 0);
                return [
                    'key' => $p->key,
                    'label' => $p->label,
                    'institutions_count' => $count,
                    'users_count' => (int) ($row->users_count ?? 0),
                    'adoption_pct' => round(($count / $totalInstitutions) * 100, 1),
                ];
            })->values();

            $inactiveCount = $institutions->where('is_inactive', true)->where('is_active', true)->count();
            $noAdminCount = $institutions->where('admins_count', 0)->where('is_active', true)->count();

            return response()->json([
                'data' => [
                    'summary' => [
                        'institutions' => $institutions->count(),
                        'active_institutions' => $institutions->where('is_active', true)->count(),
                        'inactive_activity_count' => $inactiveCount,
                        'no_admin_count' => $noAdminCount,
                        'inactive_days_threshold' => $inactiveDays,
                        'total_students' => (int) Student::where('status', 'Aktif')->count(),
                        'total_teachers' => (int) Employee::where('type', 'Guru')->where('status', 'Aktif')->count(),
                    ],
                    'modules' => $modules,
                    'institutions' => $institutions->values(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load adoption monitoring', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memuat monitoring adopsi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

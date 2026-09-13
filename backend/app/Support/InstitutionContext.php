<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\EmployeeInstitutionAssignment;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class InstitutionContext
{
    public const COOKIE_ACTIVE_INSTITUTION = 'active_institution_id';
    public const HEADER_ACTIVE_INSTITUTION = 'X-Institution-Id';

    /**
     * Modul yang boleh dibawa ke sekolah non-induk (peran guru/staf pengajar).
     * Jabatan tambahan (Kepala Sekolah, Waka, dll.) hanya berlaku di sekolah induk.
     */
    public const NON_INDUK_TEACHING_PERMISSIONS = [
        'teaching_journal',
        'grade_book',
        'schedule',
        'online_exam',
        'attendance',
    ];

    /**
     * Default akses operasional guru di sekolah non-induk.
     * Persuratan tidak ikut — administratif di sekolah induk saja.
     */
    public const NON_INDUK_DEFAULT_PERMISSIONS = [
        'teaching_journal',
        'grade_book',
        'schedule',
    ];

    private static function requestBag(?Request $request = null): ?Request
    {
        if ($request) {
            return $request;
        }

        try {
            return request();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function employeeFor(User $user, ?Request $request = null): ?Employee
    {
        $request = self::requestBag($request);
        $cacheKey = 'ic_employee_for_'.$user->id;
        if ($request && $request->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        $employee = $user->employeeProfile()->first() ?? $user->teacherProfile()->first();
        $request?->attributes->set($cacheKey, $employee);

        return $employee;
    }

    /**
     * Resolve employee record for user at a specific institution.
     * Supports guru dengan pegawai terpisah per sekolah (email sama, employee_id beda)
     * serta assignment non-induk ke sekolah lain.
     */
    public static function employeeForInstitution(User $user, ?int $institutionId = null, ?Request $request = null): ?Employee
    {
        $request = self::requestBag($request);
        $institutionId = $institutionId ?? self::resolveActiveInstitutionId($user, $request);
        $cacheKey = 'ic_employee_for_inst_'.$user->id.'_'.($institutionId ?: 0);

        if ($request && $request->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        if (! $user->email) {
            $employee = self::employeeFor($user, $request);
            $request?->attributes->set($cacheKey, $employee);

            return $employee;
        }

        $baseQuery = Employee::query()->where('email', $user->email);

        if ($institutionId) {
            $direct = (clone $baseQuery)->where('institution_id', $institutionId)->first();
            if ($direct) {
                $request?->attributes->set($cacheKey, $direct);

                return $direct;
            }

            $assigned = (clone $baseQuery)
                ->whereHas('assignments', function ($q) use ($institutionId) {
                    $q->approved()->where('institution_id', $institutionId);
                })
                ->first();
            if ($assigned) {
                $request?->attributes->set($cacheKey, $assigned);

                return $assigned;
            }
        }

        $employee = self::employeeFor($user, $request);
        $request?->attributes->set($cacheKey, $employee);

        return $employee;
    }

    /**
     * Sekolah yang boleh diakses user: induk + assignment non-induk approved.
     *
     * @return Collection<int, array{id:int,name:?string,npsn:?string,affiliation:string}>
     */
    public static function availableInstitutions(User $user, ?Request $request = null): Collection
    {
        $request = self::requestBag($request);
        $cacheKey = 'ic_available_institutions_'.$user->id;
        if ($request && $request->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        $items = collect();

        if ($user->institution_id) {
            $home = $user->relationLoaded('institution')
                ? $user->institution
                : Institution::query()->find($user->institution_id);

            if ($home) {
                $items->push([
                    'id' => (int) $home->id,
                    'name' => $home->name,
                    'npsn' => $home->npsn,
                    'is_demo' => (bool) ($home->is_demo ?? false),
                    'affiliation' => 'induk',
                ]);
            }
        }

        $employee = self::employeeFor($user, $request);
        if (! $employee) {
            $request?->attributes->set($cacheKey, $items->values());

            return $items->values();
        }

        $assignments = EmployeeInstitutionAssignment::query()
            ->approved()
            ->where('employee_id', $employee->id)
            ->with('institution:id,name,npsn,is_demo')
            ->get();

        foreach ($assignments as $assignment) {
            $inst = $assignment->institution;
            if (! $inst) {
                continue;
            }
            if ($items->contains(fn ($row) => (int) $row['id'] === (int) $inst->id)) {
                continue;
            }
            $items->push([
                'id' => (int) $inst->id,
                'name' => $inst->name,
                'npsn' => $inst->npsn,
                'is_demo' => (bool) ($inst->is_demo ?? false),
                'affiliation' => 'non_induk',
            ]);
        }

        $result = $items->values();
        $request?->attributes->set($cacheKey, $result);

        return $result;
    }

    /**
     * ID institusi yang boleh diakses user (dihitung sekali per request).
     *
     * @return list<int>
     */
    public static function accessibleInstitutionIds(User $user, ?Request $request = null): array
    {
        $request = self::requestBag($request);
        $cacheKey = 'ic_accessible_ids_'.$user->id;
        if ($request && $request->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        $ids = [];

        if ($user->institution_id) {
            $ids[] = (int) $user->institution_id;
        }

        $employee = self::employeeFor($user, $request);
        if ($employee) {
            if ($employee->institution_id) {
                $ids[] = (int) $employee->institution_id;
            }

            $assignmentIds = EmployeeInstitutionAssignment::query()
                ->approved()
                ->where('employee_id', $employee->id)
                ->pluck('institution_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $ids = array_merge($ids, $assignmentIds);
        }

        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));
        $request?->attributes->set($cacheKey, $ids);

        return $ids;
    }

    public static function canAccessInstitution(User $user, int $institutionId, ?Request $request = null): bool
    {
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        return in_array($institutionId, self::accessibleInstitutionIds($user, $request), true);
    }

    public static function employeeBelongsToInstitution(Employee $employee, int $institutionId, ?Request $request = null): bool
    {
        if ((int) $employee->institution_id === $institutionId) {
            return true;
        }

        $request = self::requestBag($request);
        $cacheKey = 'ic_emp_belongs_'.$employee->id.'_'.$institutionId;
        if ($request && $request->attributes->has($cacheKey)) {
            return (bool) $request->attributes->get($cacheKey);
        }

        $belongs = EmployeeInstitutionAssignment::query()
            ->approved()
            ->where('employee_id', $employee->id)
            ->where('institution_id', $institutionId)
            ->exists();

        $request?->attributes->set($cacheKey, $belongs);

        return $belongs;
    }

    /**
     * Resolve active institution for request.
     * Forced attribute (switch) > header > cookie > home.
     */
    public static function resolveActiveInstitutionId(User $user, ?Request $request = null): ?int
    {
        $request = self::requestBag($request);
        $cacheKey = 'ic_resolved_active_'.$user->id;

        // Sudah di-resolve di request ini (kecuali force baru dari switch).
        if ($request && $request->attributes->has($cacheKey) && ! $request->attributes->get('force_institution_id')) {
            return $request->attributes->get($cacheKey);
        }

        $resolved = null;

        // Explicit force (e.g. switch-institution) must win over stale header/cookie.
        if ($request?->attributes->get('force_institution_id')) {
            $forced = (int) $request->attributes->get('current_institution_id');
            if ($forced > 0 && self::canAccessInstitution($user, $forced, $request)) {
                $resolved = $forced;
            }
        }

        if ($resolved === null) {
            $candidates = [];

            $header = $request?->header(self::HEADER_ACTIVE_INSTITUTION);
            if ($header !== null && $header !== '') {
                $candidates[] = (int) $header;
            }

            $cookie = $request?->cookie(self::COOKIE_ACTIVE_INSTITUTION);
            if ($cookie !== null && $cookie !== '') {
                $candidates[] = (int) $cookie;
            }

            $attr = $request?->attributes->get('current_institution_id');
            if ($attr !== null && $attr !== '') {
                $candidates[] = (int) $attr;
            }

            foreach ($candidates as $id) {
                if ($id > 0 && self::canAccessInstitution($user, $id, $request)) {
                    $resolved = $id;
                    break;
                }
            }
        }

        if ($resolved === null) {
            $resolved = $user->institution_id ? (int) $user->institution_id : null;
        }

        $request?->attributes->set($cacheKey, $resolved);

        return $resolved;
    }

    /**
     * Force active institution on the current request (used by switch-institution).
     */
    public static function forceActiveInstitution(Request $request, User $user, int $institutionId): void
    {
        if (! self::canAccessInstitution($user, $institutionId, $request)) {
            return;
        }

        $request->attributes->set('force_institution_id', true);
        $request->attributes->set('current_institution_id', $institutionId);
        $request->attributes->set(
            'current_affiliation',
            self::affiliationFor($user, $institutionId)
        );
        $request->headers->set(self::HEADER_ACTIVE_INSTITUTION, (string) $institutionId);
        $request->attributes->remove('effective_permission_keys');
        $request->attributes->remove('ic_resolved_active_'.$user->id);
    }

    public static function isHomeInstitution(User $user, ?int $institutionId): bool
    {
        if (! $institutionId || ! $user->institution_id) {
            return false;
        }

        return (int) $user->institution_id === (int) $institutionId;
    }

    /**
     * Permission keys stored on the user (not scoped).
     *
     * @return array<int, string>
     */
    public static function storedPermissionKeys(User $user, ?Request $request = null): array
    {
        $request = self::requestBag($request);
        $cacheKey = 'ic_stored_permission_keys_'.$user->id;
        if ($request && $request->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        if ($user->relationLoaded('permissions')) {
            $keys = $user->permissions->pluck('key')->filter()->values()->all();
        } else {
            $keys = $user->permissions()->pluck('permissions.key')->filter()->values()->all();
        }

        $request?->attributes->set($cacheKey, $keys);

        return $keys;
    }

    /**
     * Effective module permissions for the active institution.
     * At non-induk schools, elevated duty permissions (Kepala Sekolah, Waka, …) are stripped.
     *
     * @return array<int, string>
     */
    public static function effectivePermissionKeys(User $user, ?int $institutionId = null, ?Request $request = null): array
    {
        $request = self::requestBag($request);
        $institutionId = $institutionId
            ?? ($request?->attributes->get('current_institution_id') !== null
                && $request->attributes->get('current_institution_id') !== ''
                ? (int) $request->attributes->get('current_institution_id')
                : null)
            ?? self::resolveActiveInstitutionId($user, $request);

        $cacheKey = 'effective_permission_keys';
        $cacheFor = $institutionId ?: 0;
        $cached = $request?->attributes->get($cacheKey);
        if (is_array($cached) && ($cached['_for'] ?? null) === $cacheFor) {
            return $cached['keys'];
        }

        $stored = self::storedPermissionKeys($user, $request);

        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            $keys = $stored;
        } elseif (! $institutionId || self::isHomeInstitution($user, $institutionId)) {
            $keys = $stored;
        } else {
            $keys = array_values(array_intersect($stored, self::NON_INDUK_TEACHING_PERMISSIONS));

            // Guru/staf di sekolah non-induk tetap butuh akses operasional mengajar,
            // meski permission tersimpan hanya dari jabatan di sekolah induk.
            if (in_array($user->role, ['teacher', 'staff'], true)) {
                $keys = array_values(array_unique(array_merge(
                    $keys,
                    self::NON_INDUK_DEFAULT_PERMISSIONS
                )));
            }
        }

        $request?->attributes->set($cacheKey, ['_for' => $cacheFor, 'keys' => $keys]);

        return $keys;
    }

    public static function hasEffectivePermission(User $user, string $moduleKey, ?int $institutionId = null, ?Request $request = null): bool
    {
        return in_array($moduleKey, self::effectivePermissionKeys($user, $institutionId, $request), true);
    }

    /**
     * Resolve institution for a user on a request (shared by piket and other modules).
     * Platform admins may use any explicit institution_id; school users only if canAccess.
     */
    public static function resolveForUser(User $user, ?Request $request = null, $requestInstitutionId = null): ?int
    {
        $request = self::requestBag($request);

        // Platform-level only — never treat institution_admin as global.
        if ($user->isAdminOrSuperAdmin()) {
            $id = $requestInstitutionId ?: (
                $request?->attributes->get('current_institution_id')
                ?: $user->institution_id
            );

            return $id !== null && $id !== '' ? (int) $id : null;
        }

        if ($requestInstitutionId) {
            $requested = (int) $requestInstitutionId;
            if (self::canAccessInstitution($user, $requested, $request)) {
                return $requested;
            }
        }

        return self::resolveActiveInstitutionId($user, $request);
    }

    public static function affiliationFor(User $user, ?int $activeInstitutionId): ?string
    {
        if (! $activeInstitutionId) {
            return null;
        }

        if ($user->institution_id && (int) $user->institution_id === (int) $activeInstitutionId) {
            return 'induk';
        }

        return 'non_induk';
    }

    public static function applyToRequest(Request $request, User $user): int|null
    {
        $activeId = self::resolveActiveInstitutionId($user, $request);
        if ($activeId) {
            $request->attributes->set('current_institution_id', $activeId);
            $request->attributes->set(
                'current_affiliation',
                self::affiliationFor($user, $activeId)
            );
        }

        return $activeId;
    }

    /**
     * Active academic year / semester for an institution (cached per request).
     *
     * @return array{active_academic_year_id:?int,active_semester_id:?int}
     */
    public static function institutionActivePeriod(int $institutionId, ?Request $request = null): array
    {
        $request = self::requestBag($request);
        $cacheKey = 'ic_inst_period_'.$institutionId;
        if ($request && $request->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        $row = Institution::query()
            ->where('id', $institutionId)
            ->first(['active_academic_year_id', 'active_semester_id']);

        $period = [
            'active_academic_year_id' => $row?->active_academic_year_id
                ? (int) $row->active_academic_year_id
                : null,
            'active_semester_id' => $row?->active_semester_id
                ? (int) $row->active_semester_id
                : null,
        ];

        $request?->attributes->set($cacheKey, $period);

        return $period;
    }

    /**
     * Scope employees visible at an institution (induk + approved non-induk).
     */
    public static function scopeEmployeesForInstitution($query, int $institutionId)
    {
        return $query->where(function ($q) use ($institutionId) {
            $q->where('institution_id', $institutionId)
                ->orWhereHas('assignments', function ($assignmentQuery) use ($institutionId) {
                    $assignmentQuery->where('institution_id', $institutionId)
                        ->where('status', 'approved');
                });
        });
    }
}

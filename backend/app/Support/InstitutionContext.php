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
        'correspondence',
        'teaching_journal',
        'grade_book',
        'schedule',
        'online_exam',
        'attendance',
    ];

    /**
     * Default akses operasional guru di sekolah non-induk.
     */
    public const NON_INDUK_DEFAULT_PERMISSIONS = [
        'correspondence',
        'teaching_journal',
        'grade_book',
        'schedule',
    ];

    public static function employeeFor(User $user): ?Employee
    {
        return $user->employeeProfile()->first() ?? $user->teacherProfile()->first();
    }

    /**
     * Sekolah yang boleh diakses user: induk + assignment non-induk approved.
     *
     * @return Collection<int, array{id:int,name:?string,npsn:?string,affiliation:string}>
     */
    public static function availableInstitutions(User $user): Collection
    {
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
                    'affiliation' => 'induk',
                ]);
            }
        }

        $employee = self::employeeFor($user);
        if (!$employee) {
            return $items->values();
        }

        $assignments = EmployeeInstitutionAssignment::query()
            ->approved()
            ->where('employee_id', $employee->id)
            ->with('institution:id,name,npsn')
            ->get();

        foreach ($assignments as $assignment) {
            $inst = $assignment->institution;
            if (!$inst) {
                continue;
            }
            if ($items->contains(fn ($row) => (int) $row['id'] === (int) $inst->id)) {
                continue;
            }
            $items->push([
                'id' => (int) $inst->id,
                'name' => $inst->name,
                'npsn' => $inst->npsn,
                'affiliation' => 'non_induk',
            ]);
        }

        return $items->values();
    }

    public static function canAccessInstitution(User $user, int $institutionId): bool
    {
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        if ($user->institution_id && (int) $user->institution_id === $institutionId) {
            return true;
        }

        $employee = self::employeeFor($user);
        if (!$employee) {
            return false;
        }

        if ((int) $employee->institution_id === $institutionId) {
            return true;
        }

        return EmployeeInstitutionAssignment::query()
            ->approved()
            ->where('employee_id', $employee->id)
            ->where('institution_id', $institutionId)
            ->exists();
    }

    public static function employeeBelongsToInstitution(Employee $employee, int $institutionId): bool
    {
        if ((int) $employee->institution_id === $institutionId) {
            return true;
        }

        return EmployeeInstitutionAssignment::query()
            ->approved()
            ->where('employee_id', $employee->id)
            ->where('institution_id', $institutionId)
            ->exists();
    }

    /**
     * Resolve active institution for request.
     * Forced attribute (switch) > header > cookie > home.
     */
    public static function resolveActiveInstitutionId(User $user, ?Request $request = null): ?int
    {
        $request = $request ?? request();
        $candidates = [];

        // Explicit force (e.g. switch-institution) must win over stale header/cookie.
        if ($request?->attributes->get('force_institution_id')) {
            $forced = (int) $request->attributes->get('current_institution_id');
            if ($forced > 0 && self::canAccessInstitution($user, $forced)) {
                return $forced;
            }
        }

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
            if ($id > 0 && self::canAccessInstitution($user, $id)) {
                return $id;
            }
        }

        return $user->institution_id ? (int) $user->institution_id : null;
    }

    /**
     * Force active institution on the current request (used by switch-institution).
     */
    public static function forceActiveInstitution(Request $request, User $user, int $institutionId): void
    {
        if (!self::canAccessInstitution($user, $institutionId)) {
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
    }

    public static function isHomeInstitution(User $user, ?int $institutionId): bool
    {
        if (!$institutionId || !$user->institution_id) {
            return false;
        }

        return (int) $user->institution_id === (int) $institutionId;
    }

    /**
     * Permission keys stored on the user (not scoped).
     *
     * @return array<int, string>
     */
    public static function storedPermissionKeys(User $user): array
    {
        if ($user->relationLoaded('permissions')) {
            return $user->permissions->pluck('key')->filter()->values()->all();
        }

        return $user->permissions()->pluck('permissions.key')->filter()->values()->all();
    }

    /**
     * Effective module permissions for the active institution.
     * At non-induk schools, elevated duty permissions (Kepala Sekolah, Waka, …) are stripped.
     *
     * @return array<int, string>
     */
    public static function effectivePermissionKeys(User $user, ?int $institutionId = null, ?Request $request = null): array
    {
        $request = $request ?? request();
        $institutionId = $institutionId
            ?? ($request?->attributes->get('current_institution_id') !== null
                ? (int) $request->attributes->get('current_institution_id')
                : null)
            ?? self::resolveActiveInstitutionId($user, $request);

        $cacheKey = 'effective_permission_keys';
        $cacheFor = $institutionId ?: 0;
        $cached = $request?->attributes->get($cacheKey);
        if (is_array($cached) && ($cached['_for'] ?? null) === $cacheFor) {
            return $cached['keys'];
        }

        $stored = self::storedPermissionKeys($user);

        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            $keys = $stored;
        } elseif (!$institutionId || self::isHomeInstitution($user, $institutionId)) {
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
     * Admins may use explicit request institution_id; teachers/staff only if canAccess.
     */
    public static function resolveForUser(User $user, ?Request $request = null, $requestInstitutionId = null): ?int
    {
        $request = $request ?? request();

        if ($user->isSuperAdmin() || $user->isAdmin() || $user->isInstitutionAdmin()) {
            $id = $requestInstitutionId ?: (
                $request->attributes->get('current_institution_id')
                ?: $user->institution_id
            );

            return $id !== null && $id !== '' ? (int) $id : null;
        }

        if ($requestInstitutionId) {
            $requested = (int) $requestInstitutionId;
            if (self::canAccessInstitution($user, $requested)) {
                return $requested;
            }
        }

        return self::resolveActiveInstitutionId($user, $request);
    }

    public static function affiliationFor(User $user, ?int $activeInstitutionId): ?string
    {
        if (!$activeInstitutionId) {
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

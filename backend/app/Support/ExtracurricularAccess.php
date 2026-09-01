<?php

namespace App\Support;

use App\Models\AdditionalDuty;
use App\Models\Employee;
use App\Models\Extracurricular;
use App\Models\Permission;
use App\Models\User;
use App\Support\InstitutionContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExtracurricularAccess
{
    public static function employeeFor(User $user, ?int $institutionId = null): ?Employee
    {
        return InstitutionContext::employeeForInstitution($user, $institutionId);
    }

    public static function canManageAll(User $user): bool
    {
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            return true;
        }

        $activeId = request()->attributes->get('current_institution_id');
        $activeId = $activeId !== null && $activeId !== ''
            ? (int) $activeId
            : ($user->institution_id ? (int) $user->institution_id : null);

        // Jabatan koordinator hanya berlaku di sekolah induk.
        if ($activeId && InstitutionContext::affiliationFor($user, $activeId) === 'non_induk') {
            return false;
        }

        return self::hasDuty($user, 'koordinator_ekstrakurikuler');
    }

    public static function hasDuty(User $user, string $dutyKey): bool
    {
        $employee = self::employeeFor($user);
        if (!$employee) {
            return false;
        }

        return $employee->additionalDuties()
            ->where('additional_duties.key', $dutyKey)
            ->exists();
    }

    /**
     * Whether the user is pembina (supervisor) of at least one ekstrakurikuler.
     * When $institutionId is set, only clubs at that school count.
     */
    public static function isSupervisor(User $user, ?int $institutionId = null): bool
    {
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            return false;
        }

        $resolvedInstitutionId = $institutionId ?? InstitutionContext::resolveActiveInstitutionId($user);
        $employee = self::employeeFor($user, $resolvedInstitutionId);
        if (!$employee) {
            return false;
        }

        $query = Extracurricular::query()
            ->where('supervisor_employee_id', $employee->id);

        if ($resolvedInstitutionId) {
            $query->where('institution_id', $resolvedInstitutionId);
        }

        return $query->exists();
    }

    public static function canAccess(User $user, Extracurricular $extracurricular): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!InstitutionContext::canAccessInstitution($user, (int) $extracurricular->institution_id)) {
            return false;
        }

        if (self::canManageAll($user)) {
            return true;
        }

        $employee = self::employeeFor($user, (int) $extracurricular->institution_id);

        return $employee && (int) $extracurricular->supervisor_employee_id === (int) $employee->id;
    }

    public static function canMutateCatalog(User $user): bool
    {
        return self::canManageAll($user);
    }

    /**
     * KKM diisi pembina ekskul yang bersangkutan (bukan admin/koordinator katalog).
     */
    public static function canSetKkm(User $user, Extracurricular $extracurricular): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $employee = self::employeeFor($user, (int) $extracurricular->institution_id);
        if (!$employee) {
            return false;
        }

        return (int) $extracurricular->supervisor_employee_id === (int) $employee->id;
    }

    /**
     * Scope query for list: pembina only sees supervised ekskul.
     */
    public static function scopeVisible($query, User $user)
    {
        if (self::canManageAll($user)) {
            return $query;
        }

        $institutionId = InstitutionContext::resolveActiveInstitutionId($user);
        $employee = self::employeeFor($user, $institutionId);
        if (!$employee) {
            return $query->whereRaw('1 = 0');
        }

        if ($institutionId) {
            $query = $query->where('institution_id', $institutionId);
        }

        return $query->where('supervisor_employee_id', $employee->id);
    }

    /**
     * When a guru is set as pembina ekskul: grant module permission + tugas tambahan pembina.
     */
    public static function grantAccessForEmployee(?int $employeeId): void
    {
        if (!$employeeId) {
            return;
        }

        try {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                return;
            }

            $now = now();
            $permId = Permission::where('key', 'extracurricular')->value('id');
            $dutyId = AdditionalDuty::where('key', 'pembina_ekstrakurikuler')->value('id');

            // Pastikan duty pembina punya permission extracurricular
            if ($permId && $dutyId) {
                $exists = DB::table('additional_duty_permissions')
                    ->where('additional_duty_id', $dutyId)
                    ->where('permission_id', $permId)
                    ->exists();
                if (!$exists) {
                    DB::table('additional_duty_permissions')->insert([
                        'additional_duty_id' => $dutyId,
                        'permission_id' => $permId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // Lampirkan tugas tambahan pembina jika belum ada
            if ($dutyId && !$employee->additionalDuties()->where('additional_duties.id', $dutyId)->exists()) {
                $employee->additionalDuties()->attach($dutyId, [
                    'started_at' => $now->toDateString(),
                ]);
            }

            if (!$employee->email || !$permId) {
                return;
            }

            $user = User::where('email', $employee->email)
                ->whereIn('role', ['teacher', 'staff'])
                ->first();
            if (!$user) {
                return;
            }

            if (!$user->permissions()->where('permissions.id', $permId)->exists()) {
                $user->permissions()->attach($permId);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to grant extracurricular access for supervisor', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

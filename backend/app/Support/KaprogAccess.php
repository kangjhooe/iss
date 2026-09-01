<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\ProgramKeahlian;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Scope Kaprog (kepala_program_keahlian) ke program/jurusan yang diampu.
 * Admin/institution_admin tidak di-scope.
 */
class KaprogAccess
{
    public const DUTY_KEY = 'kepala_program_keahlian';

    public static function employeeFor(User $user, ?int $institutionId = null): ?Employee
    {
        return InstitutionContext::employeeForInstitution($user, $institutionId);
    }

    public static function isKaprog(User $user): bool
    {
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            return false;
        }

        $employee = self::employeeFor($user, InstitutionContext::resolveActiveInstitutionId($user));
        if (! $employee) {
            return false;
        }

        return $employee->additionalDuties()
            ->where('additional_duties.key', self::DUTY_KEY)
            ->exists();
    }

    /**
     * ID program keahlian yang diampu Kaprog. Kosong = belum di-assign jurusan
     * (dianggap tidak punya scope kelas — list kosong sampai admin assign).
     *
     * @return list<int>
     */
    public static function programIds(User $user): array
    {
        $employee = self::employeeFor($user, InstitutionContext::resolveActiveInstitutionId($user));
        if (! $employee) {
            return [];
        }

        return $employee->programKeahlians()
            ->pluck('program_keahlian.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Apakah user perlu di-scope (Kaprog non-admin).
     */
    public static function shouldScope(User $user): bool
    {
        return self::isKaprog($user);
    }

    public static function canAccessProgram(User $user, int $programKeahlianId): bool
    {
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        $program = ProgramKeahlian::query()->find($programKeahlianId);
        if ($user->isInstitutionAdmin()) {
            return $program
                && $user->institution_id
                && (int) $program->institution_id === (int) $user->institution_id;
        }

        if (! self::isKaprog($user)) {
            return true; // bukan Kaprog — akses modul biasa tanpa scope jurusan
        }

        return in_array($programKeahlianId, self::programIds($user), true);
    }

    public static function canAccessClass(User $user, SchoolClass $class): bool
    {
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        if ($user->isInstitutionAdmin()) {
            return $user->institution_id
                && (int) $class->institution_id === (int) $user->institution_id;
        }

        if (! self::isKaprog($user)) {
            return true;
        }

        $programId = $class->program_keahlian_id ? (int) $class->program_keahlian_id : null;
        if (! $programId) {
            return false;
        }

        return self::canAccessProgram($user, $programId);
    }

    /**
     * Scope query kelas untuk Kaprog.
     */
    public static function scopeClasses($query, User $user)
    {
        if (! self::shouldScope($user)) {
            return $query;
        }

        $ids = self::programIds($user);
        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('program_keahlian_id', $ids);
    }

    /**
     * @return Collection<int, ProgramKeahlian>
     */
    public static function programs(User $user): Collection
    {
        $employee = self::employeeFor($user, InstitutionContext::resolveActiveInstitutionId($user));
        if (! $employee) {
            return collect();
        }

        return $employee->programKeahlians()->orderBy('name')->get();
    }
}

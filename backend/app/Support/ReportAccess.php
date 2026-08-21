<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\User;

/**
 * Halaman Laporan & Statistik (/report): hanya admin sekolah dan Kepala Sekolah.
 */
class ReportAccess
{
    public const MODULE_KEY = 'report';

    public const DUTY_KEY = 'kepala_sekolah';

    public static function employeeFor(User $user): ?Employee
    {
        return $user->employeeProfile()->first() ?? $user->teacherProfile()->first();
    }

    public static function isKepalaSekolah(User $user): bool
    {
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            return false;
        }

        $employee = self::employeeFor($user);

        return $employee ? self::employeeIsKepalaSekolah($employee) : false;
    }

    public static function employeeIsKepalaSekolah(Employee $employee): bool
    {
        return $employee->additionalDuties()
            ->where('additional_duties.key', self::DUTY_KEY)
            ->where(function ($q) {
                $q->whereNull('employee_additional_duties.ended_at')
                    ->orWhere('employee_additional_duties.ended_at', '>', now());
            })
            ->exists();
    }

    public static function canAccess(User $user): bool
    {
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            return true;
        }

        if (! self::isKepalaSekolah($user)) {
            return false;
        }

        return InstitutionContext::isHomeInstitution($user, $user->currentInstitutionId());
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function sanitizeKeys(array $keys, bool $allowReport): array
    {
        $keys = array_values(array_filter(
            $keys,
            fn ($key) => is_string($key) && $key !== '' && $key !== self::MODULE_KEY
        ));

        if ($allowReport) {
            $keys[] = self::MODULE_KEY;
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function sanitizeKeysForUser(User $user, array $keys): array
    {
        return self::sanitizeKeys($keys, self::isKepalaSekolah($user));
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function sanitizeKeysForEmployee(Employee $employee, array $keys): array
    {
        return self::sanitizeKeys($keys, self::employeeIsKepalaSekolah($employee));
    }
}

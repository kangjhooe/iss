<?php

namespace App\Support;

use App\Models\AdditionalDuty;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\PiketSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PiketAccess
{
    public static function employeeFor(User $user): ?Employee
    {
        return $user->employeeProfile()->first() ?? $user->teacherProfile()->first();
    }

    public static function canManage(User $user): bool
    {
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            return true;
        }

        return $user->hasModuleAccess('guru_piket_manage');
    }

    public static function canAccess(User $user): bool
    {
        if (self::canManage($user)) {
            return true;
        }

        if ($user->hasModuleAccess('guru_piket')) {
            return true;
        }

        // Guru yang sudah ada di jadwal piket boleh akses modul (lapor kejadian / log)
        return self::isScheduled($user);
    }

    public static function hasDuty(User $user): bool
    {
        $employee = self::employeeFor($user);
        if (!$employee) {
            return false;
        }

        return $employee->additionalDuties()
            ->where('additional_duties.key', 'guru_piket')
            ->exists();
    }

    public static function isScheduled(User $user, ?int $institutionId = null): bool
    {
        if (!Schema::hasTable('piket_schedules')) {
            return false;
        }

        $employee = self::employeeFor($user);
        if (!$employee) {
            return false;
        }

        try {
            $query = PiketSchedule::query()->where('employee_id', $employee->id);
            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }

            return $query->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function isOnDutyToday(User $user, ?int $institutionId = null): bool
    {
        if (!Schema::hasTable('piket_schedules')) {
            return false;
        }

        $employee = self::employeeFor($user);
        if (!$employee) {
            return false;
        }

        try {
            $day = Carbon::today()->dayOfWeekIso;

            $query = PiketSchedule::query()
                ->where('employee_id', $employee->id)
                ->where('day_of_week', $day);
            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }

            return $query->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Pastikan pegawai terjadwal piket punya duty + permission guru_piket.
     */
    public static function ensureEmployeeAccess(Employee $employee): void
    {
        try {
            if (!Schema::hasTable('permissions') || !Schema::hasTable('additional_duties')) {
                return;
            }

            $now = now();
            $duty = AdditionalDuty::query()->where('key', 'guru_piket')->first();
            if (!$duty) {
                return;
            }

            $existing = DB::table('employee_additional_duties')
                ->where('employee_id', $employee->id)
                ->where('additional_duty_id', $duty->id)
                ->first();

            if (!$existing) {
                DB::table('employee_additional_duties')->insert([
                    'employee_id' => $employee->id,
                    'additional_duty_id' => $duty->id,
                    'started_at' => $now->toDateString(),
                    'ended_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } elseif ($existing->ended_at && Carbon::parse($existing->ended_at)->lte(now())) {
                DB::table('employee_additional_duties')
                    ->where('employee_id', $employee->id)
                    ->where('additional_duty_id', $duty->id)
                    ->update([
                        'ended_at' => null,
                        'started_at' => $existing->started_at ?: $now->toDateString(),
                        'updated_at' => $now,
                    ]);
            }

            $permId = Permission::query()->where('key', 'guru_piket')->value('id');
            if (!$permId || !$employee->email) {
                return;
            }

            $user = User::query()->where('email', $employee->email)->first();
            if (!$user) {
                return;
            }

            $hasPerm = DB::table('user_permissions')
                ->where('user_id', $user->id)
                ->where('permission_id', $permId)
                ->exists();

            if (!$hasPerm) {
                DB::table('user_permissions')->insert([
                    'user_id' => $user->id,
                    'permission_id' => $permId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('PiketAccess::ensureEmployeeAccess failed', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Backfill akses untuk semua pegawai yang sudah ada di jadwal piket.
     */
    public static function backfillScheduledEmployees(): int
    {
        if (!Schema::hasTable('piket_schedules')) {
            return 0;
        }

        $employeeIds = PiketSchedule::query()->distinct()->pluck('employee_id');
        $count = 0;
        foreach ($employeeIds as $id) {
            $employee = Employee::find($id);
            if ($employee) {
                self::ensureEmployeeAccess($employee);
                $count++;
            }
        }

        return $count;
    }
}

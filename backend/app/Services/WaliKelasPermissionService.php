<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Memberikan akses modul otomatis kepada guru yang diangkat sebagai wali kelas.
 */
class WaliKelasPermissionService
{
    /**
     * Permission keys yang otomatis diberikan ke wali kelas.
     * - bk_report: laporan BK read-only untuk siswa di kelasnya saja
     * - grade_book: buku nilai
     * - teaching_journal: jurnal mengajar
     * - report: laporan
     *
     * Modul `class` / `student` sengaja tidak diberikan:
     * manajemen kelas & data siswa penuh milik admin/TU.
     * Roster siswa kelas wali tersedia lewat dashboard guru.
     * Modul `violation` / `counseling` tidak diberikan: itu akses BK penuh.
     */
    public static function waliKelasPermissionKeys(): array
    {
        return [
            'bk_report',
            'grade_book',
            'teaching_journal',
            'report',
        ];
    }

    /**
     * Keys yang pernah di-grant otomatis ke wali lalu dicabut (kecuali dari tugas tambahan).
     */
    public static function revokedWaliKelasPermissionKeys(): array
    {
        return [
            'student',
            'violation',
            'counseling',
            'class',
        ];
    }

    /**
     * Sync privilege wali kelas untuk akun guru.
     * - Jika masih wali: grant keys wali + cabut keys yang tidak boleh (kecuali dari tugas tambahan).
     * - Jika tidak lagi wali: cabut keys yang hanya dari paket wali (kecuali tugas tambahan / keys non-wali).
     */
    public function syncWaliKelasPermissionsForEmployee(int $employeeId): void
    {
        $employee = Employee::find($employeeId);
        if (!$employee || !$employee->email) {
            return;
        }

        $user = User::where('email', $employee->email)->first();
        if (!$user) {
            Log::debug('Wali kelas permission: no user account for employee', [
                'employee_id' => $employeeId,
                'email' => $employee->email,
            ]);
            return;
        }

        $isStillWali = SchoolClass::query()
            ->where('teacher_id', $employeeId)
            ->exists();

        if ($isStillWali) {
            $this->grantWaliKelasPermissionsToEmployee($employeeId);
            return;
        }

        $this->revokeWaliOnlyPermissionsFromEmployee($employeeId);
    }

    /**
     * Berikan privilege akses wali kelas ke akun guru (merge dengan permission yang sudah ada).
     * Mencabut student/violation/counseling/class kecuali berasal dari tugas tambahan aktif.
     */
    public function grantWaliKelasPermissionsToEmployee(int $employeeId): void
    {
        $employee = Employee::find($employeeId);
        if (!$employee || !$employee->email) {
            return;
        }

        $user = User::where('email', $employee->email)->first();
        if (!$user) {
            Log::debug('Wali kelas permission: no user account for employee', [
                'employee_id' => $employeeId,
                'email' => $employee->email,
            ]);
            return;
        }

        $existingKeys = $user->permissions()->pluck('key')->toArray();
        $waliKeys = static::waliKelasPermissionKeys();
        $mergedKeys = array_values(array_unique(array_merge($existingKeys, $waliKeys)));

        $dutyKeys = $this->activeDutyPermissionKeys($employee);
        $toRevoke = [];
        foreach (static::revokedWaliKelasPermissionKeys() as $key) {
            if (in_array($key, $dutyKeys, true)) {
                continue;
            }
            $toRevoke[] = $key;
        }

        $mergedKeys = array_values(array_diff($mergedKeys, $toRevoke));

        $permissionIds = Permission::whereIn('key', $mergedKeys)->pluck('id')->all();
        $user->permissions()->sync($permissionIds);

        Log::info('Wali kelas permissions granted', [
            'user_id' => $user->id,
            'employee_id' => $employeeId,
            'added_keys' => array_values(array_diff($waliKeys, $existingKeys)),
            'removed_keys' => array_values(array_intersect($existingKeys, $toRevoke)),
        ]);
    }

    /**
     * Cabut permission paket wali dari guru yang sudah tidak menjadi wali kelas.
     * Keys di waliKelasPermissionKeys dicabut kecuali masih ada di tugas tambahan.
     * Keys di revoked list juga dipastikan dicabut (kecuali tugas tambahan).
     */
    public function revokeWaliOnlyPermissionsFromEmployee(int $employeeId): void
    {
        $employee = Employee::find($employeeId);
        if (!$employee || !$employee->email) {
            return;
        }

        $user = User::where('email', $employee->email)->first();
        if (!$user) {
            return;
        }

        $dutyKeys = $this->activeDutyPermissionKeys($employee);
        $existingKeys = $user->permissions()->pluck('key')->toArray();

        $keysToRemove = array_values(array_unique(array_merge(
            static::waliKelasPermissionKeys(),
            static::revokedWaliKelasPermissionKeys()
        )));

        $toRevoke = array_values(array_filter(
            $keysToRemove,
            fn (string $key) => !in_array($key, $dutyKeys, true)
        ));

        $remainingKeys = array_values(array_diff($existingKeys, $toRevoke));
        $permissionIds = Permission::whereIn('key', $remainingKeys)->pluck('id')->all();
        $user->permissions()->sync($permissionIds);

        Log::info('Wali kelas permissions revoked (no longer homeroom)', [
            'user_id' => $user->id,
            'employee_id' => $employeeId,
            'removed_keys' => array_values(array_intersect($existingKeys, $toRevoke)),
        ]);
    }

    /**
     * Sinkronkan ulang semua wali aktif + cabut student/class/violation/counseling dari paket lama.
     */
    public function syncAllCurrentWaliKelas(): int
    {
        $teacherIds = SchoolClass::query()
            ->whereNotNull('teacher_id')
            ->distinct()
            ->pluck('teacher_id');

        $synced = 0;
        foreach ($teacherIds as $teacherId) {
            $this->grantWaliKelasPermissionsToEmployee((int) $teacherId);
            $synced++;
        }

        return $synced;
    }

    /**
     * @deprecated Gunakan syncAllCurrentWaliKelas()
     */
    public function revokeClassPermissionFromAllWaliKelas(): int
    {
        return $this->syncAllCurrentWaliKelas();
    }

    /**
     * @return array<int, string>
     */
    protected function activeDutyPermissionKeys(Employee $employee): array
    {
        return $employee->additionalDuties()
            ->where(function ($q) {
                $q->whereNull('employee_additional_duties.ended_at')
                    ->orWhere('employee_additional_duties.ended_at', '>', now());
            })
            ->with('permissions')
            ->get()
            ->flatMap(fn ($duty) => $duty->permissions->pluck('key'))
            ->unique()
            ->values()
            ->all();
    }
}

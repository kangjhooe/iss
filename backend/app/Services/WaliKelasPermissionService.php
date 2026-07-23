<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\SchoolClass;
use App\Models\User;
use App\Support\TeacherAccess;
use Illuminate\Support\Facades\Log;

/**
 * Memberikan akses modul otomatis kepada guru yang diangkat sebagai wali kelas.
 *
 * Akses mengajar (nilai, jurnal, jadwal) ada di {@see TeacherAccess} dan
 * tidak dicabut saat guru berhenti jadi wali.
 */
class WaliKelasPermissionService
{
    /**
     * Permission keys khusus wali kelas (bukan paket mengajar umum).
     * - bk_report: laporan BK read-only untuk siswa di kelasnya saja
     * - report: laporan umum sekolah
     *
     * Modul `grade_book` / `teaching_journal` / `schedule` milik semua guru mapel
     * (lihat TeacherAccess), sengaja tidak masuk daftar ini agar tidak dicabut
     * saat status wali berakhir.
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
     * - Jika masih wali: grant keys wali + pastikan paket mengajar + cabut keys terlarang.
     * - Jika tidak lagi wali: cabut keys khusus wali saja (bukan grade_book/teaching_journal).
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
     * Juga memastikan paket mengajar (nilai/jurnal) tetap ada.
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
        $mergedKeys = array_values(array_unique(array_merge(
            $existingKeys,
            TeacherAccess::defaultPermissionKeys(),
            $waliKeys
        )));

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
     * Cabut permission khusus wali dari guru yang sudah tidak menjadi wali kelas.
     * Tidak mencabut paket mengajar (grade_book, teaching_journal, schedule, correspondence).
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

        $remainingKeys = TeacherAccess::mergeTeachingDefaults(
            array_values(array_diff($existingKeys, $toRevoke))
        );
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

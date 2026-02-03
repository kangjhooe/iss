<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Memberikan akses modul otomatis kepada guru yang diangkat sebagai wali kelas.
 */
class WaliKelasPermissionService
{
    /**
     * Permission keys yang otomatis diberikan ke wali kelas.
     * - student: data lengkap siswa per kelas
     * - violation: catatan pelanggaran dan prestasi
     * - counseling: konseling
     * - grade_book: buku nilai
     * - teaching_journal: jurnal mengajar
     * - report: laporan
     * - class: data kelas
     */
    public static function waliKelasPermissionKeys(): array
    {
        return [
            'student',
            'violation',
            'counseling',
            'grade_book',
            'teaching_journal',
            'report',
            'class',
        ];
    }

    /**
     * Berikan privilege akses wali kelas ke akun guru (merge dengan permission yang sudah ada).
     * Jika pegawai tidak punya user account, tidak ada yang dilakukan.
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

        $permissionIds = Permission::whereIn('key', $mergedKeys)->pluck('id')->all();
        $user->permissions()->sync($permissionIds);

        Log::info('Wali kelas permissions granted', [
            'user_id' => $user->id,
            'employee_id' => $employeeId,
            'added_keys' => array_diff($waliKeys, $existingKeys),
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sempitkan akses Kepala Sekolah: pengawasan & penandatanganan,
     * bukan CRUD operasional (itu untuk Waka / TU / Operator / Admin).
     *
     * Default baru: institution, correspondence, report, teacher_appreciation, guru_piket, bk_report.
     */
    public function up(): void
    {
        $dutyId = DB::table('additional_duties')->where('key', 'kepala_sekolah')->value('id');
        if (!$dutyId) {
            return;
        }

        DB::table('additional_duties')
            ->where('id', $dutyId)
            ->update([
                'description' => 'Pimpinan sekolah: pengawasan, laporan, dan penandatanganan (bukan pengelolaan operasional harian)',
                'updated_at' => now(),
            ]);

        $newKeys = [
            'institution',
            'correspondence',
            'report',
            'teacher_appreciation',
            'guru_piket',
            'bk_report',
        ];

        // Pastikan permission pengawasan ada (instalasi yang belum punya bk_report / dll.)
        $now = now();
        $ensurePermissions = [
            'bk_report' => 'Laporan BK (Kelas)',
            'teacher_appreciation' => 'Apresiasi Guru',
            'guru_piket' => 'Guru Piket',
        ];
        foreach ($ensurePermissions as $key => $label) {
            if (!DB::table('permissions')->where('key', $key)->exists()) {
                DB::table('permissions')->insert([
                    'key' => $key,
                    'label' => $label,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Semua key yang pernah digantung ke KS (seed awal + migrasi lanjutan)
        $legacyKeys = [
            'institution', 'student', 'teacher', 'facility', 'inventory', 'class', 'correspondence',
            'report', 'violation', 'schedule', 'counseling', 'teaching_journal', 'grade_book',
            'digital_archive', 'attendance', 'guest_book',
            'online_exam', 'teacher_appreciation',
            'guru_piket', 'guru_piket_manage', 'bk_report',
        ];

        $permissionIdsByKey = DB::table('permissions')->pluck('id', 'key');
        $newIds = collect($newKeys)
            ->map(fn ($k) => $permissionIdsByKey[$k] ?? null)
            ->filter()
            ->values()
            ->all();
        $legacyIds = collect($legacyKeys)
            ->map(fn ($k) => $permissionIdsByKey[$k] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Ganti mapping duty → permission
        DB::table('additional_duty_permissions')
            ->where('additional_duty_id', $dutyId)
            ->delete();

        $now = now();
        $rows = [];
        foreach ($newIds as $permId) {
            $rows[] = [
                'additional_duty_id' => $dutyId,
                'permission_id' => $permId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($rows)) {
            DB::table('additional_duty_permissions')->insert($rows);
        }

        // Resync akun pegawai yang memegang tugas KS
        $employeeIds = DB::table('employee_additional_duties')
            ->where('additional_duty_id', $dutyId)
            ->where(function ($q) {
                $q->whereNull('ended_at')->orWhere('ended_at', '>', now());
            })
            ->pluck('employee_id')
            ->unique();

        if ($employeeIds->isEmpty()) {
            return;
        }

        $employees = DB::table('employee')
            ->whereIn('id', $employeeIds)
            ->whereNotNull('email')
            ->get(['id', 'email']);

        foreach ($employees as $employee) {
            $user = DB::table('user')->where('email', $employee->email)->first();
            if (!$user) {
                continue;
            }

            // Permission dari SEMUA tugas tambahan aktif (setelah mapping KS baru)
            $dutyPermIds = DB::table('employee_additional_duties')
                ->join(
                    'additional_duty_permissions',
                    'employee_additional_duties.additional_duty_id',
                    '=',
                    'additional_duty_permissions.additional_duty_id'
                )
                ->where('employee_additional_duties.employee_id', $employee->id)
                ->where(function ($q) {
                    $q->whereNull('employee_additional_duties.ended_at')
                        ->orWhere('employee_additional_duties.ended_at', '>', now());
                })
                ->pluck('additional_duty_permissions.permission_id')
                ->unique()
                ->all();

            // Cabut legacy KS yang tidak lagi didapat dari duty lain
            foreach ($legacyIds as $permId) {
                if (in_array($permId, $dutyPermIds, true)) {
                    continue;
                }
                DB::table('user_permissions')
                    ->where('user_id', $user->id)
                    ->where('permission_id', $permId)
                    ->delete();
            }

            // Pastikan permission KS baru ada
            foreach ($newIds as $permId) {
                $exists = DB::table('user_permissions')
                    ->where('user_id', $user->id)
                    ->where('permission_id', $permId)
                    ->exists();
                if (!$exists) {
                    DB::table('user_permissions')->insert([
                        'user_id' => $user->id,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $dutyId = DB::table('additional_duties')->where('key', 'kepala_sekolah')->value('id');
        if (!$dutyId) {
            return;
        }

        DB::table('additional_duties')
            ->where('id', $dutyId)
            ->update([
                'description' => 'Pimpinan sekolah',
                'updated_at' => now(),
            ]);

        // Kembalikan mapping luas (seperti seed awal + tambahan umum)
        $restoreKeys = [
            'institution', 'student', 'teacher', 'facility', 'inventory', 'class', 'correspondence',
            'report', 'violation', 'schedule', 'counseling', 'teaching_journal', 'grade_book',
            'digital_archive', 'attendance', 'guest_book',
            'online_exam', 'teacher_appreciation',
            'guru_piket', 'guru_piket_manage',
        ];

        $permissionIdsByKey = DB::table('permissions')->pluck('id', 'key');
        DB::table('additional_duty_permissions')->where('additional_duty_id', $dutyId)->delete();

        $now = now();
        $rows = [];
        foreach ($restoreKeys as $key) {
            $permId = $permissionIdsByKey[$key] ?? null;
            if ($permId) {
                $rows[] = [
                    'additional_duty_id' => $dutyId,
                    'permission_id' => $permId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        if (!empty($rows)) {
            DB::table('additional_duty_permissions')->insert($rows);
        }
        // Tidak memulihkan user_permissions otomatis (aman; admin bisa sync ulang lewat form pegawai).
    }
};

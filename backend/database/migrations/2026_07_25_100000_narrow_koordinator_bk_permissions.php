<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sempitkan akses Koordinator BK: hanya modul BK operasional
     * (konseling + pelanggaran / laporan BK), bukan data siswa penuh
     * atau laporan statistik umum (itu untuk Waka Kesiswaan / Operator).
     *
     * Default baru: counseling, violation.
     */
    public function up(): void
    {
        $dutyId = DB::table('additional_duties')->where('key', 'koordinator_bk')->value('id');
        if (!$dutyId) {
            return;
        }

        DB::table('additional_duties')
            ->where('id', $dutyId)
            ->update([
                'description' => 'Bimbingan Konseling: konseling, pelanggaran, dan laporan BK (bukan pengelolaan data siswa / administrasi umum)',
                'updated_at' => now(),
            ]);

        $newKeys = ['counseling', 'violation'];

        // Key yang pernah digantung ke Koordinator BK (seed + migrasi violation)
        $legacyKeys = ['counseling', 'student', 'report', 'violation'];

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

        if (empty($newIds)) {
            return;
        }

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
        DB::table('additional_duty_permissions')->insert($rows);

        // Resync akun pegawai yang memegang tugas Koordinator BK aktif
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

            // Permission dari SEMUA tugas tambahan aktif (setelah mapping BK baru)
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

            // Cabut legacy BK yang tidak lagi didapat dari duty lain
            foreach ($legacyIds as $permId) {
                if (in_array($permId, $dutyPermIds, true)) {
                    continue;
                }
                DB::table('user_permissions')
                    ->where('user_id', $user->id)
                    ->where('permission_id', $permId)
                    ->delete();
            }

            // Pastikan permission BK baru ada
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
        $dutyId = DB::table('additional_duties')->where('key', 'koordinator_bk')->value('id');
        if (!$dutyId) {
            return;
        }

        DB::table('additional_duties')
            ->where('id', $dutyId)
            ->update([
                'description' => 'Bimbingan Konseling',
                'updated_at' => now(),
            ]);

        // Kembalikan mapping sebelumnya (seed + grant violation)
        $restoreKeys = ['counseling', 'student', 'report', 'violation'];

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
        // Tidak memulihkan user_permissions otomatis (admin bisa sync ulang lewat form pegawai).
    }
};

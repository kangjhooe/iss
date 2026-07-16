<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permission ringan untuk wali kelas: lihat laporan BK kelas sendiri saja.
     * Mencabut violation/counseling yang sebelumnya di-grant otomatis ke wali,
     * kecuali pegawai punya tugas tambahan yang memang memberi permission itu.
     */
    public function up(): void
    {
        $now = now();

        if (!DB::table('permissions')->where('key', 'bk_report')->exists()) {
            DB::table('permissions')->insert([
                'key' => 'bk_report',
                'label' => 'Laporan BK (Kelas)',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $bkReportId = DB::table('permissions')->where('key', 'bk_report')->value('id');
        $violationId = DB::table('permissions')->where('key', 'violation')->value('id');
        $counselingId = DB::table('permissions')->where('key', 'counseling')->value('id');

        // Semua pegawai yang sedang jadi wali kelas
        $waliEmployeeIds = DB::table('class')
            ->whereNotNull('teacher_id')
            ->distinct()
            ->pluck('teacher_id');

        if ($waliEmployeeIds->isEmpty() || !$bkReportId) {
            return;
        }

        $employees = DB::table('employee')
            ->whereIn('id', $waliEmployeeIds)
            ->whereNotNull('email')
            ->get(['id', 'email']);

        foreach ($employees as $employee) {
            $user = DB::table('user')->where('email', $employee->email)->first();
            if (!$user) {
                continue;
            }

            // Grant bk_report
            $hasBk = DB::table('user_permissions')
                ->where('user_id', $user->id)
                ->where('permission_id', $bkReportId)
                ->exists();
            if (!$hasBk) {
                DB::table('user_permissions')->insert([
                    'user_id' => $user->id,
                    'permission_id' => $bkReportId,
                ]);
            }

            // Permission dari tugas tambahan — jangan dicabut
            $dutyPermIds = DB::table('employee_additional_duties')
                ->join('additional_duty_permissions', 'employee_additional_duties.additional_duty_id', '=', 'additional_duty_permissions.additional_duty_id')
                ->where('employee_additional_duties.employee_id', $employee->id)
                ->where(function ($q) {
                    $q->whereNull('employee_additional_duties.ended_at')
                        ->orWhere('employee_additional_duties.ended_at', '>', now());
                })
                ->pluck('additional_duty_permissions.permission_id')
                ->unique()
                ->all();

            foreach ([$violationId, $counselingId] as $permId) {
                if (!$permId) {
                    continue;
                }
                if (in_array($permId, $dutyPermIds, true)) {
                    continue;
                }
                DB::table('user_permissions')
                    ->where('user_id', $user->id)
                    ->where('permission_id', $permId)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        $bkReportId = DB::table('permissions')->where('key', 'bk_report')->value('id');
        if ($bkReportId) {
            DB::table('user_permissions')->where('permission_id', $bkReportId)->delete();
            DB::table('permissions')->where('id', $bkReportId)->delete();
        }
    }
};

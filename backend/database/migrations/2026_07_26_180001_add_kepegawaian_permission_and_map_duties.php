<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (!DB::table('permissions')->where('key', 'kepegawaian')->exists()) {
            DB::table('permissions')->insert([
                'key' => 'kepegawaian',
                'label' => 'Kepegawaian',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('permissions')->where('key', 'kepegawaian')->update([
                'label' => 'Kepegawaian',
                'updated_at' => $now,
            ]);
        }

        $permissionId = DB::table('permissions')->where('key', 'kepegawaian')->value('id');
        if (!$permissionId) {
            return;
        }

        $dutyKeys = ['kepala_tata_usaha', 'operator_sekolah', 'kepala_sekolah'];
        $dutyIds = DB::table('additional_duties')
            ->whereIn('key', $dutyKeys)
            ->pluck('id', 'key');

        foreach ($dutyKeys as $dutyKey) {
            $dutyId = $dutyIds[$dutyKey] ?? null;
            if (!$dutyId) {
                continue;
            }

            $exists = DB::table('additional_duty_permissions')
                ->where('additional_duty_id', $dutyId)
                ->where('permission_id', $permissionId)
                ->exists();
            if (!$exists) {
                DB::table('additional_duty_permissions')->insert([
                    'additional_duty_id' => $dutyId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('additional_duties')->where('key', 'kepala_tata_usaha')->update([
            'description' => 'Administrasi umum, kepegawaian (cuti, SK, jabatan struktural), surat-menyurat',
            'updated_at' => $now,
        ]);

        $allDutyIds = $dutyIds->values()->all();
        if (empty($allDutyIds)) {
            return;
        }

        $employeeIds = DB::table('employee_additional_duties')
            ->whereIn('additional_duty_id', $allDutyIds)
            ->where(function ($q) {
                $q->whereNull('ended_at')->orWhere('ended_at', '>', now());
            })
            ->pluck('employee_id')
            ->unique();

        if ($employeeIds->isEmpty()) {
            return;
        }

        $emails = DB::table('employee')->whereIn('id', $employeeIds)->whereNotNull('email')->pluck('email');
        $users = DB::table('user')->whereIn('email', $emails)->get(['id', 'email']);
        $employeesByEmail = DB::table('employee')
            ->whereIn('email', $emails)
            ->pluck('id', 'email');

        foreach ($users as $user) {
            $employeeId = $employeesByEmail[$user->email] ?? null;
            if (!$employeeId) {
                continue;
            }

            $activeDutyIds = DB::table('employee_additional_duties')
                ->where('employee_id', $employeeId)
                ->whereIn('additional_duty_id', $allDutyIds)
                ->where(function ($q) {
                    $q->whereNull('ended_at')->orWhere('ended_at', '>', now());
                })
                ->pluck('additional_duty_id');

            $hasDuty = DB::table('additional_duty_permissions')
                ->whereIn('additional_duty_id', $activeDutyIds)
                ->where('permission_id', $permissionId)
                ->exists();

            if (!$hasDuty) {
                continue;
            }

            $exists = DB::table('user_permissions')
                ->where('user_id', $user->id)
                ->where('permission_id', $permissionId)
                ->exists();
            if (!$exists) {
                DB::table('user_permissions')->insert([
                    'user_id' => $user->id,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('key', 'kepegawaian')->value('id');
        if (!$permissionId) {
            return;
        }

        $dutyIds = DB::table('additional_duties')
            ->whereIn('key', ['kepala_tata_usaha', 'operator_sekolah', 'kepala_sekolah'])
            ->pluck('id');

        DB::table('additional_duty_permissions')
            ->whereIn('additional_duty_id', $dutyIds)
            ->where('permission_id', $permissionId)
            ->delete();

        DB::table('user_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();

        DB::table('additional_duties')->where('key', 'kepala_tata_usaha')->update([
            'description' => 'Administrasi umum, kepegawaian, surat-menyurat',
        ]);
    }
};

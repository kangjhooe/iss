<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (!DB::table('permissions')->where('key', 'uks')->exists()) {
            DB::table('permissions')->insert([
                'key' => 'uks',
                'label' => 'UKS',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('permissions')->where('key', 'uks')->update([
                'label' => 'UKS',
                'updated_at' => $now,
            ]);
        }

        $permissionId = DB::table('permissions')->where('key', 'uks')->value('id');
        if (!$permissionId) {
            return;
        }

        $dutyMaps = [
            'koordinator_uks' => [
                'description' => 'Usaha Kesehatan Sekolah: kunjungan UKS, rekam kesehatan, laporan',
            ],
            'waka_kesiswaan' => [
                'description' => null, // keep existing description; only add permission
            ],
        ];

        $dutyIds = DB::table('additional_duties')
            ->whereIn('key', array_keys($dutyMaps))
            ->pluck('id', 'key');

        foreach ($dutyMaps as $dutyKey => $config) {
            $dutyId = $dutyIds[$dutyKey] ?? null;
            if (!$dutyId) {
                continue;
            }

            if (!empty($config['description'])) {
                DB::table('additional_duties')->where('id', $dutyId)->update([
                    'description' => $config['description'],
                    'updated_at' => $now,
                ]);
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

            $hasUksDuty = DB::table('additional_duty_permissions')
                ->whereIn('additional_duty_id', $activeDutyIds)
                ->where('permission_id', $permissionId)
                ->exists();

            if (!$hasUksDuty) {
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
        $permissionId = DB::table('permissions')->where('key', 'uks')->value('id');
        if (!$permissionId) {
            return;
        }

        $dutyIds = DB::table('additional_duties')
            ->whereIn('key', ['koordinator_uks', 'waka_kesiswaan'])
            ->pluck('id');

        DB::table('additional_duty_permissions')
            ->whereIn('additional_duty_id', $dutyIds)
            ->where('permission_id', $permissionId)
            ->delete();

        DB::table('user_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();

        DB::table('additional_duties')->where('key', 'koordinator_uks')->update([
            'description' => 'Usaha Kesehatan Sekolah (label tugas; modul domain khusus menyusul)',
        ]);
    }
};

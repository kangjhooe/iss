<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            'pkl' => 'PKL / Prakerin (Beta)',
            'bkk' => 'BKK / Bursa Kerja (Beta)',
        ];

        foreach ($permissions as $key => $label) {
            if (!DB::table('permissions')->where('key', $key)->exists()) {
                DB::table('permissions')->insert([
                    'key' => $key,
                    'label' => $label,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('permissions')->where('key', $key)->update([
                    'label' => $label,
                    'updated_at' => $now,
                ]);
            }
        }

        $permissionIds = DB::table('permissions')->whereIn('key', array_keys($permissions))->pluck('id', 'key');

        $dutyMaps = [
            'koordinator_pkl' => [
                'permissions' => ['pkl'],
                'description' => 'Koordinasi PKL/Prakerin (modul beta)',
            ],
            'koordinator_bkk' => [
                'permissions' => ['bkk'],
                'description' => 'Bursa Kerja Khusus / penyaluran lulusan (modul beta)',
            ],
            'koordinator_hubin' => [
                'permissions' => ['pkl', 'bkk'],
                'description' => 'Hubungan industri / DU-DI (mitra, PKL, BKK — modul beta)',
            ],
            'kepala_program_keahlian' => [
                'permissions' => ['pkl'],
                'description' => 'Kaprog SMK: kurikulum/jadwal/nilai program keahlian + pantau PKL (modul beta)',
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

            DB::table('additional_duties')->where('id', $dutyId)->update([
                'description' => $config['description'],
                'updated_at' => $now,
            ]);

            foreach ($config['permissions'] as $permKey) {
                $permId = $permissionIds[$permKey] ?? null;
                if (!$permId) {
                    continue;
                }
                $exists = DB::table('additional_duty_permissions')
                    ->where('additional_duty_id', $dutyId)
                    ->where('permission_id', $permId)
                    ->exists();
                if (!$exists) {
                    DB::table('additional_duty_permissions')->insert([
                        'additional_duty_id' => $dutyId,
                        'permission_id' => $permId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
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

            $permIdsForUser = DB::table('additional_duty_permissions')
                ->whereIn('additional_duty_id', $activeDutyIds)
                ->whereIn('permission_id', $permissionIds->values()->all())
                ->pluck('permission_id')
                ->unique();

            foreach ($permIdsForUser as $permId) {
                $exists = DB::table('user_permissions')
                    ->where('user_id', $user->id)
                    ->where('permission_id', $permId)
                    ->exists();
                if (!$exists) {
                    DB::table('user_permissions')->insert([
                        'user_id' => $user->id,
                        'permission_id' => $permId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $permIds = DB::table('permissions')->whereIn('key', ['pkl', 'bkk'])->pluck('id');
        if ($permIds->isEmpty()) {
            return;
        }

        $dutyIds = DB::table('additional_duties')
            ->whereIn('key', [
                'koordinator_pkl',
                'koordinator_bkk',
                'koordinator_hubin',
                'kepala_program_keahlian',
            ])
            ->pluck('id');

        DB::table('additional_duty_permissions')
            ->whereIn('additional_duty_id', $dutyIds)
            ->whereIn('permission_id', $permIds)
            ->delete();

        DB::table('user_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('id', $permIds)->delete();

        DB::table('additional_duties')->where('key', 'koordinator_pkl')->update([
            'description' => 'Koordinasi PKL/Prakerin (label tugas; modul domain khusus menyusul)',
        ]);
        DB::table('additional_duties')->where('key', 'koordinator_bkk')->update([
            'description' => 'Bursa Kerja Khusus / penyaluran lulusan (label tugas; modul domain khusus menyusul)',
        ]);
        DB::table('additional_duties')->where('key', 'koordinator_hubin')->update([
            'description' => 'Hubungan industri / DU-DI (kerjasama, surat, profil lembaga)',
        ]);
        DB::table('additional_duties')->where('key', 'kepala_program_keahlian')->update([
            'description' => 'Kaprog SMK: kurikulum/jadwal/nilai program keahlian (bukan CRUD kesiswaan penuh)',
        ]);
    }
};

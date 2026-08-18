<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sempitkan tugas tambahan koordinator/pembina yang terlalu lebar:
     * - cabut student/report (CRUD kesiswaan & laporan umum)
     * - OSIS: pertahankan violation (disiplin)
     * - Ekskul: hanya extracurricular
     * - UKS / Pramuka: label organisasi saja (belum ada modul domain khusus)
     */
    public function up(): void
    {
        $targets = [
            'koordinator_uks' => [
                'description' => 'Usaha Kesehatan Sekolah (label tugas; modul domain khusus menyusul)',
                'new' => [],
                'legacy' => ['student', 'report'],
            ],
            'koordinator_pramuka' => [
                'description' => 'Pembina Pramuka (label tugas; akses operasional lewat ekskul/keanggotaan bila relevan)',
                'new' => [],
                'legacy' => ['student', 'report'],
            ],
            'koordinator_osis' => [
                'description' => 'Pembina OSIS: pelanggaran/disiplin (bukan pengelolaan data siswa penuh)',
                'new' => ['violation'],
                'legacy' => ['student', 'violation', 'report'],
            ],
            'koordinator_ekstrakurikuler' => [
                'description' => 'Koordinasi ekstrakurikuler (bukan pengelolaan data siswa / laporan umum)',
                'new' => ['extracurricular'],
                'legacy' => ['student', 'report', 'extracurricular'],
            ],
            'pembina_ekstrakurikuler' => [
                'description' => 'Pembina satu atau lebih ekstrakurikuler (bukan pengelolaan data siswa / laporan umum)',
                'new' => ['extracurricular'],
                'legacy' => ['student', 'report', 'extracurricular'],
            ],
        ];

        foreach ($targets as $dutyKey => $config) {
            $this->narrowDuty($dutyKey, $config['description'], $config['new'], $config['legacy']);
        }
    }

    public function down(): void
    {
        $restore = [
            'koordinator_uks' => [
                'description' => 'Usaha Kesehatan Sekolah',
                'keys' => ['student', 'report'],
            ],
            'koordinator_pramuka' => [
                'description' => 'Pembina Pramuka',
                'keys' => ['student', 'report'],
            ],
            'koordinator_osis' => [
                'description' => 'Pembina OSIS',
                'keys' => ['student', 'violation', 'report'],
            ],
            'koordinator_ekstrakurikuler' => [
                'description' => 'Koordinasi ekstrakurikuler',
                'keys' => ['student', 'report', 'extracurricular'],
            ],
            'pembina_ekstrakurikuler' => [
                'description' => 'Pembina satu atau lebih ekstrakurikuler',
                'keys' => ['student', 'report', 'extracurricular'],
            ],
        ];

        $permissionIdsByKey = DB::table('permissions')->pluck('id', 'key');
        $now = now();

        foreach ($restore as $dutyKey => $config) {
            $dutyId = DB::table('additional_duties')->where('key', $dutyKey)->value('id');
            if (!$dutyId) {
                continue;
            }

            DB::table('additional_duties')
                ->where('id', $dutyId)
                ->update([
                    'description' => $config['description'],
                    'updated_at' => $now,
                ]);

            DB::table('additional_duty_permissions')->where('additional_duty_id', $dutyId)->delete();

            $rows = [];
            foreach ($config['keys'] as $key) {
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
        }
        // Tidak memulihkan user_permissions otomatis.
    }

    /**
     * @param  list<string>  $newKeys
     * @param  list<string>  $legacyKeys
     */
    private function narrowDuty(string $dutyKey, string $description, array $newKeys, array $legacyKeys): void
    {
        $dutyId = DB::table('additional_duties')->where('key', $dutyKey)->value('id');
        if (!$dutyId) {
            return;
        }

        $now = now();

        DB::table('additional_duties')
            ->where('id', $dutyId)
            ->update([
                'description' => $description,
                'updated_at' => $now,
            ]);

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

        DB::table('additional_duty_permissions')
            ->where('additional_duty_id', $dutyId)
            ->delete();

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

        $employeeIds = DB::table('employee_additional_duties')
            ->where('additional_duty_id', $dutyId)
            ->where(function ($q) {
                $q->whereNull('ended_at')->orWhere('ended_at', '>', now());
            })
            ->pluck('employee_id')
            ->unique();

        if ($employeeIds->isEmpty() || empty($legacyIds)) {
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

            foreach ($legacyIds as $permId) {
                if (in_array($permId, $dutyPermIds, true)) {
                    continue;
                }
                DB::table('user_permissions')
                    ->where('user_id', $user->id)
                    ->where('permission_id', $permId)
                    ->delete();
            }

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
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laporan umum sekolah hanya untuk admin dan Kepala Sekolah.
     * Cabut mapping `report` dari tugas tambahan lain, lalu rapikan user_permissions.
     */
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('additional_duties')) {
            return;
        }

        $reportId = DB::table('permissions')->where('key', 'report')->value('id');
        $ksDutyId = DB::table('additional_duties')->where('key', 'kepala_sekolah')->value('id');
        if (! $reportId) {
            return;
        }

        $now = now();

        if ($ksDutyId) {
            $exists = DB::table('additional_duty_permissions')
                ->where('additional_duty_id', $ksDutyId)
                ->where('permission_id', $reportId)
                ->exists();
            if (! $exists) {
                DB::table('additional_duty_permissions')->insert([
                    'additional_duty_id' => $ksDutyId,
                    'permission_id' => $reportId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $query = DB::table('additional_duty_permissions')->where('permission_id', $reportId);
        if ($ksDutyId) {
            $query->where('additional_duty_id', '!=', $ksDutyId);
        }
        $query->delete();

        if (! Schema::hasTable('user_permissions') || ! Schema::hasTable('employee')) {
            return;
        }

        $ksUserIds = collect();
        if ($ksDutyId && Schema::hasTable('employee_additional_duties')) {
            $ksEmails = DB::table('employee_additional_duties')
                ->join('employee', 'employee.id', '=', 'employee_additional_duties.employee_id')
                ->where('employee_additional_duties.additional_duty_id', $ksDutyId)
                ->where(function ($q) {
                    $q->whereNull('employee_additional_duties.ended_at')
                        ->orWhere('employee_additional_duties.ended_at', '>', now());
                })
                ->whereNotNull('employee.email')
                ->pluck('employee.email')
                ->unique()
                ->filter();

            if ($ksEmails->isNotEmpty()) {
                $ksUserIds = DB::table('user')
                    ->whereIn('email', $ksEmails->all())
                    ->whereIn('role', ['teacher', 'staff'])
                    ->pluck('id');
            }
        }

        $holderIds = $ksUserIds->map(fn ($id) => (int) $id)->all();

        DB::table('user_permissions')
            ->where('permission_id', $reportId)
            ->when($holderIds !== [], fn ($q) => $q->whereNotIn('user_id', $holderIds))
            ->delete();

        foreach ($holderIds as $userId) {
            $exists = DB::table('user_permissions')
                ->where('user_id', $userId)
                ->where('permission_id', $reportId)
                ->exists();
            if (! $exists) {
                DB::table('user_permissions')->insert([
                    'user_id' => $userId,
                    'permission_id' => $reportId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('additional_duty_permissions')) {
            return;
        }

        $reportId = DB::table('permissions')->where('key', 'report')->value('id');
        if (! $reportId) {
            return;
        }

        $restoreDutyKeys = [
            'waka_kurikulum',
            'waka_kesiswaan',
            'waka_sarpras',
            'waka_humas',
            'kepala_tata_usaha',
            'bendahara',
            'ketua_perpus',
            'kepala_lab',
            'koordinator_literasi',
            'operator_sekolah',
        ];

        $dutyIds = DB::table('additional_duties')->whereIn('key', $restoreDutyKeys)->pluck('id');
        $now = now();
        foreach ($dutyIds as $dutyId) {
            $exists = DB::table('additional_duty_permissions')
                ->where('additional_duty_id', $dutyId)
                ->where('permission_id', $reportId)
                ->exists();
            if (! $exists) {
                DB::table('additional_duty_permissions')->insert([
                    'additional_duty_id' => $dutyId,
                    'permission_id' => $reportId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};

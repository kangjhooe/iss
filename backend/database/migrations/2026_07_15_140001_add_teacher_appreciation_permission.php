<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('key', 'teacher_appreciation')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'key' => 'teacher_appreciation',
                'label' => 'Apresiasi Guru',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permId = DB::table('permissions')->where('key', 'teacher_appreciation')->value('id');
        if (!$permId) {
            return;
        }

        $now = now();
        $dutyKeys = ['kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'operator_sekolah'];
        $dutyIds = DB::table('additional_duties')->whereIn('key', $dutyKeys)->pluck('id');

        foreach ($dutyIds as $dutyId) {
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

        // Sync to existing users who already have those duties
        $employeeIds = DB::table('employee_additional_duties')
            ->whereIn('additional_duty_id', $dutyIds->all())
            ->pluck('employee_id')
            ->unique();

        if ($employeeIds->isEmpty()) {
            return;
        }

        $emails = DB::table('employee')->whereIn('id', $employeeIds)->whereNotNull('email')->pluck('email');
        $userIds = DB::table('user')->whereIn('email', $emails)->pluck('id');

        foreach ($userIds as $userId) {
            $has = DB::table('user_permissions')
                ->where('user_id', $userId)
                ->where('permission_id', $permId)
                ->exists();
            if (!$has) {
                DB::table('user_permissions')->insert([
                    'user_id' => $userId,
                    'permission_id' => $permId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permId = DB::table('permissions')->where('key', 'teacher_appreciation')->value('id');
        if ($permId) {
            DB::table('additional_duty_permissions')->where('permission_id', $permId)->delete();
            DB::table('user_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }
};

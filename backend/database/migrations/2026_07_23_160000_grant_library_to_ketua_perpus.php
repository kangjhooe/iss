<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permId = DB::table('permissions')->where('key', 'library')->value('id');
        $dutyId = DB::table('additional_duties')->where('key', 'ketua_perpus')->value('id');
        if (!$permId || !$dutyId) {
            return;
        }

        $now = now();

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

        // Sync ke user yang sudah punya tugas ketua_perpus
        $employeeIds = DB::table('employee_additional_duties')
            ->where('additional_duty_id', $dutyId)
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
        $permId = DB::table('permissions')->where('key', 'library')->value('id');
        $dutyId = DB::table('additional_duties')->where('key', 'ketua_perpus')->value('id');
        if (!$permId || !$dutyId) {
            return;
        }

        DB::table('additional_duty_permissions')
            ->where('additional_duty_id', $dutyId)
            ->where('permission_id', $permId)
            ->delete();
    }
};

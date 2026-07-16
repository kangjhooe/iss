<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permId = DB::table('permissions')->where('key', 'extracurricular')->value('id');
        if (!$permId) {
            return;
        }

        $now = now();
        $dutyKeys = ['pembina_ekstrakurikuler', 'koordinator_ekstrakurikuler'];
        $dutyIds = DB::table('additional_duties')->whereIn('key', $dutyKeys)->pluck('id', 'key');

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

        // Sync to existing users whose employee has those duties
        $employeeIds = DB::table('employee_additional_duties')
            ->whereIn('additional_duty_id', $dutyIds->values()->all())
            ->pluck('employee_id')
            ->unique();

        if ($employeeIds->isEmpty()) {
            return;
        }

        $emails = DB::table('employee')->whereIn('id', $employeeIds)->whereNotNull('email')->pluck('email');
        $userIds = DB::table('user')->whereIn('email', $emails)->pluck('id');

        foreach ($userIds as $userId) {
            $exists = DB::table('user_permissions')
                ->where('user_id', $userId)
                ->where('permission_id', $permId)
                ->exists();
            if (!$exists) {
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
        $permId = DB::table('permissions')->where('key', 'extracurricular')->value('id');
        if (!$permId) {
            return;
        }

        $dutyIds = DB::table('additional_duties')
            ->whereIn('key', ['pembina_ekstrakurikuler', 'koordinator_ekstrakurikuler'])
            ->pluck('id');

        DB::table('additional_duty_permissions')
            ->whereIn('additional_duty_id', $dutyIds)
            ->where('permission_id', $permId)
            ->delete();
    }
};

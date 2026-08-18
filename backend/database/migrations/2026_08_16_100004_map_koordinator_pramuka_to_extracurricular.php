<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissionId = DB::table('permissions')->where('key', 'extracurricular')->value('id');
        $dutyId = DB::table('additional_duties')->where('key', 'koordinator_pramuka')->value('id');
        if (!$permissionId || !$dutyId) {
            return;
        }

        DB::table('additional_duties')->where('id', $dutyId)->update([
            'description' => 'Pembina Pramuka: kelola ekstrakurikuler Pramuka (flag is_pramuka)',
            'updated_at' => $now,
        ]);

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

        // Grant extracurricular permission to users with active koordinator_pramuka duty
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

        $emails = DB::table('employee')->whereIn('id', $employeeIds)->whereNotNull('email')->pluck('email');
        $userIds = DB::table('user')->whereIn('email', $emails)->pluck('id');

        foreach ($userIds as $userId) {
            $has = DB::table('user_permissions')
                ->where('user_id', $userId)
                ->where('permission_id', $permissionId)
                ->exists();
            if (!$has) {
                DB::table('user_permissions')->insert([
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('key', 'extracurricular')->value('id');
        $dutyId = DB::table('additional_duties')->where('key', 'koordinator_pramuka')->value('id');
        if (!$permissionId || !$dutyId) {
            return;
        }

        DB::table('additional_duty_permissions')
            ->where('additional_duty_id', $dutyId)
            ->where('permission_id', $permissionId)
            ->delete();

        DB::table('additional_duties')->where('id', $dutyId)->update([
            'description' => 'Pembina Pramuka (label tugas; akses operasional lewat ekskul/keanggotaan bila relevan)',
            'updated_at' => now(),
        ]);
    }
};

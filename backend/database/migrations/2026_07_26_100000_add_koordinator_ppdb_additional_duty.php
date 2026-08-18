<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tugas tambahan Koordinator PPDB → modul ppdb.
     */
    public function up(): void
    {
        $now = now();
        $permId = DB::table('permissions')->where('key', 'ppdb')->value('id');
        if (!$permId) {
            return;
        }

        $dutyId = DB::table('additional_duties')->where('key', 'koordinator_ppdb')->value('id');
        if (!$dutyId) {
            $sort = (int) DB::table('additional_duties')->max('sort_order') + 1;
            $dutyId = DB::table('additional_duties')->insertGetId([
                'key' => 'koordinator_ppdb',
                'label' => 'Koordinator PPDB',
                'description' => 'Koordinasi Penerimaan Peserta Didik Baru (periode, jalur, pendaftar, pembayaran)',
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('additional_duties')->where('id', $dutyId)->update([
                'label' => 'Koordinator PPDB',
                'description' => 'Koordinasi Penerimaan Peserta Didik Baru (periode, jalur, pendaftar, pembayaran)',
                'updated_at' => $now,
            ]);
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

        // Sync user_permissions for active holders
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
        $dutyId = DB::table('additional_duties')->where('key', 'koordinator_ppdb')->value('id');
        if (!$dutyId) {
            return;
        }

        $permId = DB::table('permissions')->where('key', 'ppdb')->value('id');

        DB::table('employee_additional_duties')->where('additional_duty_id', $dutyId)->delete();
        DB::table('additional_duty_permissions')->where('additional_duty_id', $dutyId)->delete();
        DB::table('additional_duties')->where('id', $dutyId)->delete();

        // Jangan cabut user_permissions ppdb secara massal — bisa dari sumber lain (manual/admin).
        unset($permId);
    }
};

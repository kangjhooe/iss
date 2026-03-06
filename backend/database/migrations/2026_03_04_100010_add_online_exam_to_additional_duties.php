<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permId = DB::table('permissions')->where('key', 'online_exam')->value('id');
        if (!$permId) {
            return;
        }
        $dutyIds = DB::table('additional_duties')
            ->whereIn('key', ['kepala_sekolah', 'waka_kurikulum'])
            ->pluck('id');
        $now = now();
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
    }

    public function down(): void
    {
        $permId = DB::table('permissions')->where('key', 'online_exam')->value('id');
        if ($permId) {
            DB::table('additional_duty_permissions')->where('permission_id', $permId)->delete();
        }
    }
};

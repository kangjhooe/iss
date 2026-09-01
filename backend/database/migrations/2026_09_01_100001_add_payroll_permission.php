<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('permissions')->where('key', 'payroll')->exists()) {
            DB::table('permissions')->insert([
                'key' => 'payroll',
                'label' => 'Penggajian',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permId = DB::table('permissions')->where('key', 'payroll')->value('id');
        if (! $permId) {
            return;
        }

        $dutyKeys = ['bendahara', 'kepala_tata_usaha', 'operator_sekolah'];
        $now = now();

        foreach ($dutyKeys as $dutyKey) {
            $dutyId = DB::table('additional_duties')->where('key', $dutyKey)->value('id');
            if (! $dutyId) {
                continue;
            }

            $exists = DB::table('additional_duty_permissions')
                ->where('additional_duty_id', $dutyId)
                ->where('permission_id', $permId)
                ->exists();

            if (! $exists) {
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
        $permId = DB::table('permissions')->where('key', 'payroll')->value('id');
        if ($permId) {
            DB::table('additional_duty_permissions')->where('permission_id', $permId)->delete();
            DB::table('user_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cabut modul Persuratan dari guru/staf yang mendapatkannya lewat paket
     * mengajar default. Pertahankan untuk admin/institution_admin/super_admin
     * dan pegawai yang punya tugas tambahan yang memetakan correspondence.
     */
    public function up(): void
    {
        $permId = DB::table('permissions')->where('key', 'correspondence')->value('id');
        if (!$permId) {
            return;
        }

        $dutyIdsWithCorrespondence = DB::table('additional_duty_permissions')
            ->where('permission_id', $permId)
            ->pluck('additional_duty_id');

        $emailsKeep = collect();
        if ($dutyIdsWithCorrespondence->isNotEmpty()) {
            $employeeIds = DB::table('employee_additional_duties')
                ->whereIn('additional_duty_id', $dutyIdsWithCorrespondence)
                ->where(function ($q) {
                    $q->whereNull('ended_at')->orWhere('ended_at', '>', now());
                })
                ->pluck('employee_id')
                ->unique();

            if ($employeeIds->isNotEmpty()) {
                $emailsKeep = DB::table('employee')
                    ->whereIn('id', $employeeIds)
                    ->whereNotNull('email')
                    ->pluck('email')
                    ->filter()
                    ->unique()
                    ->values();
            }
        }

        $keepUserIds = DB::table('user')
            ->whereIn('role', ['admin', 'institution_admin', 'super_admin'])
            ->pluck('id');

        if ($emailsKeep->isNotEmpty()) {
            $fromDuties = DB::table('user')
                ->whereIn('email', $emailsKeep->all())
                ->pluck('id');
            $keepUserIds = $keepUserIds->merge($fromDuties)->unique()->values();
        }

        $query = DB::table('user_permissions')
            ->where('permission_id', $permId);

        if ($keepUserIds->isNotEmpty()) {
            $query->whereNotIn('user_id', $keepUserIds->all());
        }

        $query->delete();
    }

    public function down(): void
    {
        // Tidak mengembalikan grant massal ke semua guru (itu yang ingin dihilangkan).
    }
};

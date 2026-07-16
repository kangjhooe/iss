<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Wali kelas tidak lagi mendapat modul student penuh.
     * Roster siswa lewat dashboard; student hanya jika dari tugas tambahan / grant manual admin.
     */
    public function up(): void
    {
        $studentId = DB::table('permissions')->where('key', 'student')->value('id');
        $classId = DB::table('permissions')->where('key', 'class')->value('id');
        $bkReportId = DB::table('permissions')->where('key', 'bk_report')->value('id');
        $gradeBookId = DB::table('permissions')->where('key', 'grade_book')->value('id');
        $teachingJournalId = DB::table('permissions')->where('key', 'teaching_journal')->value('id');
        $reportId = DB::table('permissions')->where('key', 'report')->value('id');

        $waliEmployeeIds = DB::table('class')
            ->whereNotNull('teacher_id')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('teacher_id');

        if ($waliEmployeeIds->isEmpty()) {
            return;
        }

        $employees = DB::table('employee')
            ->whereIn('id', $waliEmployeeIds)
            ->whereNotNull('email')
            ->get(['id', 'email']);

        $grantIds = array_values(array_filter([
            $bkReportId,
            $gradeBookId,
            $teachingJournalId,
            $reportId,
        ]));

        $revokeIds = array_values(array_filter([$studentId, $classId]));

        foreach ($employees as $employee) {
            $user = DB::table('user')->where('email', $employee->email)->first();
            if (!$user) {
                continue;
            }

            foreach ($grantIds as $permId) {
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

            foreach ($revokeIds as $permId) {
                if (in_array($permId, $dutyPermIds, true)) {
                    continue;
                }
                DB::table('user_permissions')
                    ->where('user_id', $user->id)
                    ->where('permission_id', $permId)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        // Tidak mengembalikan student otomatis ke wali (kebijakan baru).
    }
};

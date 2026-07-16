<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('violations')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE violations MODIFY COLUMN status ENUM(
                'pending',
                'dicatat',
                'sanksi_diberikan',
                'follow_up',
                'selesai',
                'ditolak'
            ) NOT NULL DEFAULT 'dicatat'");
        }

        Schema::table('violations', function (Blueprint $table) {
            if (!Schema::hasColumn('violations', 'piket_incident_id') && Schema::hasTable('piket_incidents')) {
                $table->foreignId('piket_incident_id')
                    ->nullable()
                    ->after('class_id')
                    ->constrained('piket_incidents')
                    ->nullOnDelete();
                $table->unique('piket_incident_id', 'violations_piket_incident_unique');
            }
            if (!Schema::hasColumn('violations', 'reviewed_by')) {
                $table->foreignId('reviewed_by')
                    ->nullable()
                    ->after('reported_by')
                    ->constrained('user')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('violations', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (!Schema::hasColumn('violations', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('follow_up_notes');
            }
        });

        $this->grantViolationToBkDuty();
    }

    public function down(): void
    {
        if (!Schema::hasTable('violations')) {
            return;
        }

        Schema::table('violations', function (Blueprint $table) {
            if (Schema::hasColumn('violations', 'piket_incident_id')) {
                $table->dropUnique('violations_piket_incident_unique');
                $table->dropConstrainedForeignId('piket_incident_id');
            }
            if (Schema::hasColumn('violations', 'reviewed_by')) {
                $table->dropConstrainedForeignId('reviewed_by');
            }
            if (Schema::hasColumn('violations', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
            if (Schema::hasColumn('violations', 'review_notes')) {
                $table->dropColumn('review_notes');
            }
        });

        DB::table('violations')->whereIn('status', ['pending', 'ditolak'])->update(['status' => 'dicatat']);

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE violations MODIFY COLUMN status ENUM(
                'dicatat',
                'sanksi_diberikan',
                'follow_up',
                'selesai'
            ) NOT NULL DEFAULT 'dicatat'");
        }
    }

    private function grantViolationToBkDuty(): void
    {
        $now = now();
        $violationPermId = DB::table('permissions')->where('key', 'violation')->value('id');
        $bkDutyId = DB::table('additional_duties')->where('key', 'koordinator_bk')->value('id');

        if (!$violationPermId || !$bkDutyId) {
            return;
        }

        $exists = DB::table('additional_duty_permissions')
            ->where('additional_duty_id', $bkDutyId)
            ->where('permission_id', $violationPermId)
            ->exists();

        if (!$exists) {
            DB::table('additional_duty_permissions')->insert([
                'additional_duty_id' => $bkDutyId,
                'permission_id' => $violationPermId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Backfill user_permissions for current BK holders
        $employeeIds = DB::table('employee_additional_duties')
            ->where('additional_duty_id', $bkDutyId)
            ->pluck('employee_id');

        if ($employeeIds->isEmpty()) {
            return;
        }

        $emails = DB::table('employee')->whereIn('id', $employeeIds)->whereNotNull('email')->pluck('email');
        $userIds = DB::table('user')->whereIn('email', $emails)->pluck('id');

        foreach ($userIds as $userId) {
            $has = DB::table('user_permissions')
                ->where('user_id', $userId)
                ->where('permission_id', $violationPermId)
                ->exists();
            if (!$has) {
                DB::table('user_permissions')->insert([
                    'user_id' => $userId,
                    'permission_id' => $violationPermId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};

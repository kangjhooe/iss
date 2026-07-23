<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piket_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->time('teacher_late_threshold')->default('07:15:00');
            $table->boolean('include_saturday')->default(false);
            $table->unsignedTinyInteger('empty_class_grace_minutes')->default(15);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('institution_id');
        });

        Schema::create('piket_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('employee')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')->comment('1=Senin .. 6=Sabtu');
            $table->string('shift', 20)->default('pagi')->comment('pagi, siang, full');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['institution_id', 'day_of_week', 'shift', 'employee_id'],
                'piket_schedules_slot_employee_unique'
            );
            $table->index(['institution_id', 'day_of_week']);
        });

        Schema::create('piket_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->date('duty_date');
            $table->foreignId('employee_id')->constrained('employee')->cascadeOnDelete();
            $table->foreignId('piket_schedule_id')->nullable()->constrained('piket_schedules')->nullOnDelete();
            $table->text('summary')->nullable();
            $table->text('handoff_notes')->nullable();
            $table->string('status', 20)->default('draft')->comment('draft, submitted, reviewed');
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institution_id', 'duty_date', 'employee_id'], 'piket_logs_day_employee_unique');
            $table->index(['institution_id', 'duty_date']);
            $table->index(['institution_id', 'status']);
        });

        Schema::create('piket_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->date('incident_date');
            $table->string('incident_type', 40)->comment('kelas_kosong, terlambat_guru, terlambat_siswa, lainnya');
            $table->unsignedTinyInteger('period')->nullable();
            $table->foreignId('class_id')->nullable()->constrained('class')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employee')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('student')->nullOnDelete();
            $table->foreignId('lesson_schedule_id')->nullable()->constrained('lesson_schedules')->nullOnDelete();
            $table->foreignId('piket_log_id')->nullable()->constrained('piket_logs')->nullOnDelete();
            $table->time('detected_at')->nullable();
            $table->unsignedSmallInteger('minutes_late')->nullable();
            $table->text('description')->nullable();
            $table->string('source', 20)->default('manual')->comment('auto, manual');
            $table->string('status', 20)->default('open')->comment('open, confirmed, resolved, dismissed');
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'incident_date', 'incident_type'], 'piket_incidents_date_type_idx');
            $table->index(['institution_id', 'status']);
        });

        $now = now();

        $permissions = [
            'guru_piket' => 'Guru Piket',
            'guru_piket_manage' => 'Kelola Guru Piket',
        ];

        foreach ($permissions as $key => $label) {
            if (!DB::table('permissions')->where('key', $key)->exists()) {
                DB::table('permissions')->insert([
                    'key' => $key,
                    'label' => $label,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $piketPermId = DB::table('permissions')->where('key', 'guru_piket')->value('id');
        $managePermId = DB::table('permissions')->where('key', 'guru_piket_manage')->value('id');
        $reportPermId = DB::table('permissions')->where('key', 'teacher_violation_report')->value('id');

        // Pastikan duty guru_piket ada
        $dutyId = DB::table('additional_duties')->where('key', 'guru_piket')->value('id');
        if (!$dutyId) {
            $maxSort = (int) DB::table('additional_duties')->max('sort_order');
            $dutyId = DB::table('additional_duties')->insertGetId([
                'key' => 'guru_piket',
                'label' => 'Guru Piket',
                'description' => 'Mengatur jadwal piket, log harian, monitoring kelas kosong & keterlambatan',
                'sort_order' => $maxSort + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('additional_duties')->where('id', $dutyId)->update([
                'description' => 'Mengatur jadwal piket, log harian, monitoring kelas kosong & keterlambatan',
                'updated_at' => $now,
            ]);
        }

        $mapDutyPerm = function (?int $dutyId, ?int $permId) use ($now) {
            if (!$dutyId || !$permId) {
                return;
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
        };

        // Duty Guru Piket: akses hub + lapor pelanggaran (jika ada)
        $mapDutyPerm($dutyId, $piketPermId);
        $mapDutyPerm($dutyId, $reportPermId);

        // KS: lihat hub piket (pengawasan), tanpa kelola jadwal
        $ksDutyId = DB::table('additional_duties')->where('key', 'kepala_sekolah')->value('id');
        if ($ksDutyId) {
            $mapDutyPerm((int) $ksDutyId, $piketPermId);
        }

        // Waka / Operator: akses + kelola
        $manageDutyKeys = ['waka_kesiswaan', 'operator_sekolah'];
        $manageDutyIds = DB::table('additional_duties')->whereIn('key', $manageDutyKeys)->pluck('id');
        foreach ($manageDutyIds as $manageDutyId) {
            $mapDutyPerm((int) $manageDutyId, $piketPermId);
            $mapDutyPerm((int) $manageDutyId, $managePermId);
        }

        // Backfill user_permissions untuk pemegang duty terkait
        $allDutyIds = collect([$dutyId, $ksDutyId])->merge($manageDutyIds)->filter()->unique()->values();
        $employeeIds = DB::table('employee_additional_duties')
            ->whereIn('additional_duty_id', $allDutyIds->all())
            ->pluck('employee_id')
            ->unique();

        if ($employeeIds->isNotEmpty()) {
            $emails = DB::table('employee')->whereIn('id', $employeeIds)->whereNotNull('email')->pluck('email');
            $userIds = DB::table('user')->whereIn('email', $emails)->pluck('id');

            foreach ($userIds as $userId) {
                // Ambil duty user untuk tentukan permission mana yang relevan
                $userEmail = DB::table('user')->where('id', $userId)->value('email');
                $empId = DB::table('employee')->where('email', $userEmail)->value('id');
                if (!$empId) {
                    continue;
                }
                $userDutyIds = DB::table('employee_additional_duties')
                    ->where('employee_id', $empId)
                    ->pluck('additional_duty_id');

                $grant = [];
                $hasKs = $ksDutyId && $userDutyIds->contains($ksDutyId);
                if ($userDutyIds->contains($dutyId) || $userDutyIds->intersect($manageDutyIds)->isNotEmpty() || $hasKs) {
                    $grant[] = $piketPermId;
                }
                if ($userDutyIds->intersect($manageDutyIds)->isNotEmpty()) {
                    $grant[] = $managePermId;
                }
                if ($userDutyIds->contains($dutyId) && $reportPermId) {
                    $grant[] = $reportPermId;
                }

                foreach (array_unique(array_filter($grant)) as $permId) {
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
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('piket_incidents');
        Schema::dropIfExists('piket_logs');
        Schema::dropIfExists('piket_schedules');
        Schema::dropIfExists('piket_settings');

        foreach (['guru_piket', 'guru_piket_manage'] as $key) {
            $permId = DB::table('permissions')->where('key', $key)->value('id');
            if ($permId) {
                DB::table('additional_duty_permissions')->where('permission_id', $permId)->delete();
                DB::table('user_permissions')->where('permission_id', $permId)->delete();
                DB::table('permissions')->where('id', $permId)->delete();
            }
        }
    }
};

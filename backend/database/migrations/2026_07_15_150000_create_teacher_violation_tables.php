<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_violation_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('code', 50)->nullable();
            $table->integer('point_weight')->default(0)->comment('Bobot poin minus (disimpan positif)');
            $table->string('category', 50)->nullable()->comment('kehadiran, kedisiplinan, administrasi, lainnya');
            $table->string('default_sanction', 255)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['institution_id', 'is_active']);
        });

        Schema::create('teacher_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');
            $table->foreignId('violation_type_id')->constrained('teacher_violation_types')->onDelete('restrict');
            $table->date('violation_date');
            $table->integer('point_value');
            $table->text('notes')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('status', 20)->default('pending')->comment('pending, approved, rejected');
            $table->string('sanction', 255)->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('user')->onDelete('set null');
            $table->foreignId('reviewed_by')->nullable()->constrained('user')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
            $table->timestamps();

            $table->index(['institution_id', 'violation_date']);
            $table->index(['employee_id', 'status']);
            $table->index(['institution_id', 'status']);
            $table->index(['academic_year_id', 'semester_id']);
        });

        $exists = DB::table('permissions')->where('key', 'teacher_violation_report')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'key' => 'teacher_violation_report',
                'label' => 'Lapor Pelanggaran Guru (Piket)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permId = DB::table('permissions')->where('key', 'teacher_violation_report')->value('id');

        $dutyId = DB::table('additional_duties')->where('key', 'guru_piket')->value('id');
        if (!$dutyId) {
            $maxSort = (int) DB::table('additional_duties')->max('sort_order');
            $dutyId = DB::table('additional_duties')->insertGetId([
                'key' => 'guru_piket',
                'label' => 'Guru Piket',
                'description' => 'Mencatat pelanggaran kedisiplinan guru (menunggu persetujuan Kepala Sekolah)',
                'sort_order' => $maxSort + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($permId && $dutyId) {
            $has = DB::table('additional_duty_permissions')
                ->where('additional_duty_id', $dutyId)
                ->where('permission_id', $permId)
                ->exists();
            if (!$has) {
                DB::table('additional_duty_permissions')->insert([
                    'additional_duty_id' => $dutyId,
                    'permission_id' => $permId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_violations');
        Schema::dropIfExists('teacher_violation_types');

        $permId = DB::table('permissions')->where('key', 'teacher_violation_report')->value('id');
        if ($permId) {
            DB::table('additional_duty_permissions')->where('permission_id', $permId)->delete();
            DB::table('user_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }

        $dutyId = DB::table('additional_duties')->where('key', 'guru_piket')->value('id');
        if ($dutyId) {
            DB::table('employee_additional_duties')->where('additional_duty_id', $dutyId)->delete();
            DB::table('additional_duty_permissions')->where('additional_duty_id', $dutyId)->delete();
            DB::table('additional_duties')->where('id', $dutyId)->delete();
        }
    }
};

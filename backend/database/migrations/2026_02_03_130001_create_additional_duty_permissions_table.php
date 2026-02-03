<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Maps each additional duty to permission keys (module access).
     */
    public function up(): void
    {
        Schema::create('additional_duty_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('additional_duty_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->primary(['additional_duty_id', 'permission_id']);
            $table->foreign('additional_duty_id')->references('id')->on('additional_duties')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });

        $permissionIdsByKey = DB::table('permissions')->pluck('id', 'key')->all();

        // duty_key => [permission_keys]
        $mapping = [
            'kepala_sekolah' => [
                'institution', 'student', 'teacher', 'facility', 'inventory', 'class', 'correspondence',
                'report', 'violation', 'schedule', 'counseling', 'teaching_journal', 'grade_book',
                'digital_archive', 'attendance', 'guest_book',
            ],
            'waka_kurikulum' => ['schedule', 'class', 'teaching_journal', 'grade_book', 'report'],
            'waka_kesiswaan' => ['student', 'violation', 'counseling', 'class', 'report'],
            'waka_sarpras' => ['facility', 'inventory', 'report'],
            'waka_humas' => ['correspondence', 'institution', 'report'],
            'kepala_tata_usaha' => ['correspondence', 'report', 'institution'],
            'bendahara' => ['report'],
            'ketua_perpus' => ['digital_archive', 'report'],
            'kepala_lab' => ['facility', 'inventory', 'report'],
            'koordinator_bk' => ['counseling', 'student', 'report'],
            'koordinator_uks' => ['student', 'report'],
            'koordinator_osis' => ['student', 'violation', 'report'],
            'koordinator_pramuka' => ['student', 'report'],
            'koordinator_literasi' => ['digital_archive', 'report'],
            'operator_sekolah' => ['report', 'institution', 'student', 'class', 'teacher'],
            'koordinator_ekstrakurikuler' => ['student', 'report'],
            'pembina_ekstrakurikuler' => ['student', 'report'],
        ];

        $dutyIdsByKey = DB::table('additional_duties')->pluck('id', 'key')->all();
        $now = now();
        $rows = [];

        foreach ($mapping as $dutyKey => $permissionKeys) {
            $dutyId = $dutyIdsByKey[$dutyKey] ?? null;
            if (!$dutyId) {
                continue;
            }
            foreach ($permissionKeys as $permKey) {
                $permId = $permissionIdsByKey[$permKey] ?? null;
                if ($permId) {
                    $rows[] = [
                        'additional_duty_id' => $dutyId,
                        'permission_id' => $permId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if (!empty($rows)) {
            DB::table('additional_duty_permissions')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_duty_permissions');
    }
};

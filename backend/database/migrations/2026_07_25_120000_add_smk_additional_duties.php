<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tugas tambahan khusus SMK:
     * - Kaprog / Kepala Bengkel: akses modul yang sudah ada
     * - Hubin: surat & profil mitra (mirip ringan Waka Humas)
     * - PKL / BKK: label organisasi dulu (modul domain menyusul)
     */
    public function up(): void
    {
        $now = now();
        $permissionIdsByKey = DB::table('permissions')->pluck('id', 'key');
        $sort = (int) DB::table('additional_duties')->max('sort_order');

        $duties = [
            'kepala_program_keahlian' => [
                'label' => 'Kepala Program Keahlian',
                'description' => 'Kaprog SMK: kurikulum/jadwal/nilai program keahlian (bukan CRUD kesiswaan penuh)',
                'permissions' => ['schedule', 'class', 'teaching_journal', 'grade_book'],
            ],
            'kepala_bengkel' => [
                'label' => 'Kepala Bengkel',
                'description' => 'Pengelola bengkel/workshop praktik kejuruan SMK',
                'permissions' => ['facility', 'inventory', 'report'],
            ],
            'koordinator_hubin' => [
                'label' => 'Koordinator Hubin',
                'description' => 'Hubungan industri / DU-DI (kerjasama, surat, profil lembaga)',
                'permissions' => ['correspondence', 'institution'],
            ],
            'koordinator_pkl' => [
                'label' => 'Koordinator PKL',
                'description' => 'Koordinasi PKL/Prakerin (label tugas; modul domain khusus menyusul)',
                'permissions' => [],
            ],
            'koordinator_bkk' => [
                'label' => 'Koordinator BKK',
                'description' => 'Bursa Kerja Khusus / penyaluran lulusan (label tugas; modul domain khusus menyusul)',
                'permissions' => [],
            ],
        ];

        foreach ($duties as $key => $config) {
            $dutyId = DB::table('additional_duties')->where('key', $key)->value('id');
            if (!$dutyId) {
                $sort++;
                $dutyId = DB::table('additional_duties')->insertGetId([
                    'key' => $key,
                    'label' => $config['label'],
                    'description' => $config['description'],
                    'sort_order' => $sort,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('additional_duties')->where('id', $dutyId)->update([
                    'label' => $config['label'],
                    'description' => $config['description'],
                    'updated_at' => $now,
                ]);
            }

            foreach ($config['permissions'] as $permKey) {
                $permId = $permissionIdsByKey[$permKey] ?? null;
                if (!$permId) {
                    continue;
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
            }
        }
    }

    public function down(): void
    {
        $keys = [
            'kepala_program_keahlian',
            'kepala_bengkel',
            'koordinator_hubin',
            'koordinator_pkl',
            'koordinator_bkk',
        ];

        $dutyIds = DB::table('additional_duties')->whereIn('key', $keys)->pluck('id');
        if ($dutyIds->isEmpty()) {
            return;
        }

        DB::table('employee_additional_duties')->whereIn('additional_duty_id', $dutyIds)->delete();
        DB::table('additional_duty_permissions')->whereIn('additional_duty_id', $dutyIds)->delete();
        DB::table('additional_duties')->whereIn('id', $dutyIds)->delete();
    }
};

<?php

namespace Database\Seeders;

use App\Services\DemoSchoolService;
use Illuminate\Database\Seeder;

/**
 * Sekolah demo publik: SMA 1 Demo Servrin (NPSN 99990001).
 *
 * Idempotent via wipe+reseed. Bisa dipanggil ulang / dijadwalkan harian:
 *   php artisan demo:reset
 *   php artisan db:seed --class=DemoSchoolSeeder
 */
class DemoSchoolSeeder extends Seeder
{
    public function run(): void
    {
        $stats = app(DemoSchoolService::class)->reset();

        $this->command?->info(sprintf(
            'Demo school ready: %s (NPSN %s) — %d classes, %d teachers, %d students%s',
            config('demo.name'),
            config('demo.npsn'),
            $stats['classes'],
            $stats['teachers'],
            $stats['students'],
            $stats['wiped'] ? ' (reset from previous)' : ''
        ));
        $this->command?->line('Admin: ' . config('demo.admin_email') . ' / ' . config('demo.password'));
        $this->command?->line('Guru:  ' . config('demo.teacher_email') . ' / ' . config('demo.password'));
        $this->command?->line('Siswa: NIK ' . config('demo.student_nik') . ' / ' . \Carbon\Carbon::parse(config('demo.student_birth_date'))->format('dmY'));
    }
}

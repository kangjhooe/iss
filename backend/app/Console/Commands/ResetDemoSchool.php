<?php

namespace App\Console\Commands;

use App\Services\DemoSchoolService;
use Illuminate\Console\Command;

class ResetDemoSchool extends Command
{
    protected $signature = 'demo:reset
                            {--force : Jalankan meskipun DEMO_SCHOOL_RESET_ENABLED=false}';

    protected $description = 'Reset sekolah demo publik (SMA 1 Demo Servrin) ke data awal';

    public function handle(DemoSchoolService $demoSchool): int
    {
        if (!config('demo.reset_enabled') && !$this->option('force')) {
            $this->warn('Demo reset dinonaktifkan (DEMO_SCHOOL_RESET_ENABLED=false). Pakai --force untuk memaksa.');
            return self::SUCCESS;
        }

        $this->info('Resetting demo school: ' . $demoSchool->schoolName() . ' (NPSN ' . $demoSchool->npsn() . ')...');

        try {
            $stats = $demoSchool->reset();
        } catch (\Throwable $e) {
            $this->error('Gagal reset demo school: ' . $e->getMessage());
            report($e);
            return self::FAILURE;
        }

        $this->info(sprintf(
            'Selesai. institution_id=%d · kelas=%d · guru=%d · siswa=%d · wiped=%s',
            $stats['institution_id'],
            $stats['classes'],
            $stats['teachers'],
            $stats['students'],
            $stats['wiped'] ? 'yes' : 'no'
        ));

        return self::SUCCESS;
    }
}

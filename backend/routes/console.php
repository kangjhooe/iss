<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('wali-kelas:revoke-class-permission', function () {
    $service = app(\App\Services\WaliKelasPermissionService::class);
    $synced = $service->syncAllCurrentWaliKelas();
    $this->info("Synced wali kelas permissions for {$synced} teacher(s) (student/class/violation/counseling stripped unless from duty).");
})->purpose('Sinkronkan ulang permission wali kelas (cabut student/class/BK penuh jika bukan dari tugas tambahan)');

Artisan::command('wali-kelas:sync-permissions', function () {
    $service = app(\App\Services\WaliKelasPermissionService::class);
    $synced = $service->syncAllCurrentWaliKelas();
    $this->info("Synced wali kelas permissions for {$synced} teacher(s).");
})->purpose('Sinkronkan ulang permission semua wali kelas aktif');

Artisan::command('swagger:generate', function () {
    // Generate Swagger documentation manually
    $this->info('Generating Swagger documentation...');
    
    try {
        // Use the generator service directly
        $generator = app(\L5Swagger\Generator::class);
        $generator->generateDocs();
        $this->info('Swagger documentation generated successfully!');
        $this->info('Access Swagger UI at: ' . rtrim(config('app.url', 'http://localhost:8000'), '/') . '/api/documentation');
    } catch (\Exception $e) {
        $this->error('Error generating Swagger documentation: ' . $e->getMessage());
        $this->error('Stack trace: ' . $e->getTraceAsString());
        return 1;
    }
    
    return 0;
})->purpose('Generate Swagger API documentation');

// Schedule academic calendar reminders (run daily at 8 AM)
Schedule::command('academic-calendar:send-reminders')
    ->dailyAt('08:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Rotate exam entry PIN every 20 minutes for started sessions
Schedule::command('exam:rotate-entry-pins')
    ->cron('*/20 * * * *')
    ->withoutOverlapping();

// Mark overdue inventory loans daily
Schedule::command('inventory:mark-loans-overdue')
    ->dailyAt('01:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Remind inventory loans due tomorrow
Schedule::command('inventory:send-loan-reminders')
    ->dailyAt('07:30')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Remind warranty expiring within 30 days
Schedule::command('inventory:send-warranty-reminders')
    ->dailyAt('08:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Reset sekolah demo publik (SMA 1 Demo Servrin) setiap hari pukul 03:00 WIB
Schedule::command('demo:reset')
    ->dailyAt('03:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

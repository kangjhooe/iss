<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('swagger:generate', function () {
    // Generate Swagger documentation manually
    $this->info('Generating Swagger documentation...');
    
    try {
        // Use the generator service directly
        $generator = app(\L5Swagger\Generator::class);
        $generator->generateDocs();
        $this->info('Swagger documentation generated successfully!');
        $this->info('Access Swagger UI at: http://localhost:8000/api/documentation');
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

<?php

namespace App\Console\Commands;

use App\Models\AcademicCalendarEvent;
use App\Models\User;
use App\Notifications\AcademicCalendarEventReminderNotification;
use App\Repositories\AcademicCalendarEventRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAcademicCalendarReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'academic-calendar:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for upcoming academic calendar events';

    /**
     * Execute the console command.
     */
    public function handle(AcademicCalendarEventRepository $repository): int
    {
        $this->info('Mengirim reminder untuk event kalender akademik...');

        // Get events that need reminders
        $events = $repository->getUpcomingForReminder(7);

        if ($events->isEmpty()) {
            $this->info('Tidak ada event yang memerlukan reminder.');
            return 0;
        }

        $sentCount = 0;
        $errorCount = 0;

        foreach ($events as $event) {
            try {
                // Get all users in the institution
                $users = User::where('institution_id', $event->institution_id)
                    ->whereNotNull('email_verified_at')
                    ->get();

                if ($users->isEmpty()) {
                    $this->warn("Tidak ada user untuk institusi ID: {$event->institution_id}");
                    continue;
                }

                // Calculate days before event
                $daysUntilEvent = now()->diffInDays($event->start_date, false);
                
                // Send notification to all users
                foreach ($users as $user) {
                    try {
                        $user->notify(new AcademicCalendarEventReminderNotification($event, $daysUntilEvent));
                        $sentCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                        Log::error('Failed to send calendar reminder', [
                            'user_id' => $user->id,
                            'event_id' => $event->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Update reminder_sent_at
                $event->update(['reminder_sent_at' => now()]);

                $this->info("Reminder dikirim untuk event: {$event->title} ({$daysUntilEvent} hari lagi)");

            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to process calendar event reminder', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Error processing event ID {$event->id}: " . $e->getMessage());
            }
        }

        $this->info("Selesai! Reminder dikirim: {$sentCount}, Error: {$errorCount}");

        return 0;
    }
}

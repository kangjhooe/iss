<?php

namespace App\Notifications;

use App\Models\AcademicCalendarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AcademicCalendarEventReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public AcademicCalendarEvent $event,
        public int $daysBefore
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $eventTypeLabels = [
            'Ujian' => 'Ujian',
            'Libur' => 'Libur',
            'Kegiatan' => 'Kegiatan',
            'Other' => 'Event Lainnya',
        ];

        $eventTypeLabel = $eventTypeLabels[$this->event->event_type] ?? $this->event->event_type;
        $daysText = $this->daysBefore == 1 ? 'besok' : "dalam {$this->daysBefore} hari";
        
        $subject = "Pengingat: {$eventTypeLabel} - {$this->event->title}";
        
        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line("Ini adalah pengingat bahwa ada event kalender akademik yang akan berlangsung {$daysText}:")
            ->line('**Event:** ' . $this->event->title)
            ->line('**Jenis:** ' . $eventTypeLabel)
            ->line('**Tanggal:** ' . $this->event->start_date->format('d F Y'));

        if (!$this->event->is_all_day && $this->event->start_time) {
            $mailMessage->line('**Waktu:** ' . $this->event->start_time->format('H:i'));
            if ($this->event->end_time) {
                $mailMessage->line('**Sampai:** ' . $this->event->end_time->format('H:i'));
            }
        }

        if ($this->event->end_date && $this->event->end_date != $this->event->start_date) {
            $mailMessage->line('**Sampai Tanggal:** ' . $this->event->end_date->format('d F Y'));
        }

        if ($this->event->description) {
            $mailMessage->line('**Deskripsi:** ' . $this->event->description);
        }

        $mailMessage->line('Terima kasih telah menggunakan sistem kalender akademik.');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $eventTypeLabels = [
            'Ujian' => 'Ujian',
            'Libur' => 'Libur',
            'Kegiatan' => 'Kegiatan',
            'Other' => 'Event Lainnya',
        ];

        $eventTypeLabel = $eventTypeLabels[$this->event->event_type] ?? $this->event->event_type;
        $daysText = $this->daysBefore == 1 ? 'besok' : "dalam {$this->daysBefore} hari";
        
        return [
            'type' => 'academic_calendar_reminder',
            'action' => 'reminder',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_type' => $this->event->event_type,
            'event_type_label' => $eventTypeLabel,
            'start_date' => $this->event->start_date->format('Y-m-d'),
            'start_time' => $this->event->start_time?->format('H:i'),
            'is_all_day' => $this->event->is_all_day,
            'days_before' => $this->daysBefore,
            'message' => "Pengingat: {$eventTypeLabel} '{$this->event->title}' akan berlangsung {$daysText}",
            'created_at' => now()->toISOString(),
        ];
    }
}

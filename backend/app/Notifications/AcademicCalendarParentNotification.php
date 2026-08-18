<?php

namespace App\Notifications;

use App\Models\AcademicCalendarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AcademicCalendarParentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AcademicCalendarEvent $event
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $eventTypeLabels = [
            'Ujian' => 'Ujian',
            'Libur' => 'Libur',
            'Kegiatan' => 'Kegiatan',
            'Other' => 'Event Lainnya',
        ];
        $eventTypeLabel = $eventTypeLabels[$this->event->event_type] ?? $this->event->event_type;
        $date = $this->event->start_date?->format('d M Y') ?? '-';

        return [
            'type' => 'academic_calendar_parent',
            'action' => 'created',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_type' => $this->event->event_type,
            'event_type_label' => $eventTypeLabel,
            'start_date' => $this->event->start_date?->format('Y-m-d'),
            'message' => "Pengumuman kalender: {$eventTypeLabel} «{$this->event->title}» ({$date})",
            'created_at' => now()->toISOString(),
        ];
    }
}

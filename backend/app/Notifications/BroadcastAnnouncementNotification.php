<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BroadcastAnnouncementNotification extends Notification
{
    use Queueable;

    public function __construct(public Announcement $announcement)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'broadcast',
            'action' => 'announcement',
            'announcement_id' => $this->announcement->id,
            'title' => $this->announcement->title,
            'message' => $this->announcement->title . ': ' . \Illuminate\Support\Str::limit($this->announcement->body, 160),
            'body' => $this->announcement->body,
            'created_at' => now()->toISOString(),
        ];
    }
}

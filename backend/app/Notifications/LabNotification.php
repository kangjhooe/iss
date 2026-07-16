<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LabNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $action,
        public string $message,
        public array $extra = []
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return array_merge([
            'type' => 'lab',
            'action' => $this->action,
            'message' => $this->message,
        ], $this->extra);
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InventoryNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $action,
        public string $message,
        public array $extra = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return array_merge([
            'type' => 'inventory',
            'action' => $this->action,
            'message' => $this->message,
            'created_at' => now()->toISOString(),
        ], $this->extra);
    }
}

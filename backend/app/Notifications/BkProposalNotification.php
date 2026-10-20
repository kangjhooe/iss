<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class BkProposalNotification extends Notification
{
    /**
     * @param  string  $kind  achievement | violation
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public string $kind,
        public string $message,
        public array $extra = [],
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $link = $this->kind === 'violation'
            ? '/bk/pelanggaran?tab=usulan'
            : '/bk/prestasi?tab=usulan';

        return array_merge([
            'type' => 'bk_proposal',
            'action' => $this->kind,
            'message' => $this->message,
            'link' => $link,
        ], $this->extra);
    }
}

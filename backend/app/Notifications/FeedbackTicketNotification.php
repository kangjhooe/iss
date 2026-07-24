<?php

namespace App\Notifications;

use App\Models\FeedbackTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FeedbackTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param string $action 'submitted' | 'updated'
     */
    public function __construct(
        public FeedbackTicket $ticket,
        public string $action
    ) {
        //
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
        $ticket = $this->ticket;
        // Queue worker unserializes without relations; always load names for the message payload.
        $ticket->loadMissing(['institution:id,name', 'submitter:id,name']);
        $institutionName = $ticket->institution?->name ?? '-';
        $submitterName = $ticket->submitter?->name ?? '-';
        $typeLabel = $ticket->type === FeedbackTicket::TYPE_BUG ? 'Laporan bug' : 'Request fitur';

        $message = match ($this->action) {
            'submitted' => "{$typeLabel} baru dari {$institutionName}: \"{$ticket->title}\" (oleh {$submitterName}).",
            'updated' => "{$typeLabel} \"{$ticket->title}\" telah diperbarui (status: {$this->statusLabel($ticket->status)}).",
            default => 'Ada pembaruan pada tiket feedback.',
        };

        return [
            'type' => 'feedback_ticket',
            'action' => $this->action,
            'ticket_id' => $ticket->id,
            'ticket_type' => $ticket->type,
            'ticket_status' => $ticket->status,
            'ticket_title' => $ticket->title,
            'institution_name' => $institutionName,
            'submitter_name' => $submitterName,
            'message' => $message,
            'created_at' => now()->toISOString(),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            FeedbackTicket::STATUS_OPEN => 'terbuka',
            FeedbackTicket::STATUS_IN_PROGRESS => 'sedang diproses',
            FeedbackTicket::STATUS_RESOLVED => 'selesai',
            FeedbackTicket::STATUS_CLOSED => 'ditutup',
            FeedbackTicket::STATUS_REJECTED => 'ditolak',
            default => $status,
        };
    }
}

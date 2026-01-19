<?php

namespace App\Notifications;

use App\Models\CorrespondenceDisposition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DispositionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public CorrespondenceDisposition $disposition,
        public string $action // 'created', 'completed', 'updated'
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
        $correspondence = $this->disposition->correspondence;
        $fromUser = $this->disposition->fromUser;
        $toUser = $this->disposition->toUser;

        $subject = match($this->action) {
            'created' => 'Disposisi Baru - ' . $correspondence->subject,
            'completed' => 'Disposisi Diselesaikan - ' . $correspondence->subject,
            'updated' => 'Disposisi Diperbarui - ' . $correspondence->subject,
            default => 'Notifikasi Disposisi - ' . $correspondence->subject,
        };

        $message = match($this->action) {
            'created' => "Anda menerima disposisi baru dari {$fromUser->name} untuk surat:",
            'completed' => "Disposisi yang Anda berikan kepada {$toUser->name} telah diselesaikan:",
            'updated' => "Disposisi yang Anda terima telah diperbarui oleh {$fromUser->name}:",
            default => 'Ada perubahan pada disposisi:',
        };

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line($message)
            ->line('**Surat:** ' . $correspondence->subject)
            ->line('**Nomor Surat:** ' . ($correspondence->letter_number ?: $correspondence->reference_number ?: '-'))
            ->line('**Instruksi:** ' . $this->disposition->instruction);

        if ($this->action === 'created') {
            $mailMessage->action('Lihat Disposisi', url('/correspondence/' . $correspondence->id));
        }

        $mailMessage->line('Terima kasih telah menggunakan sistem persuratan.');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $correspondence = $this->disposition->correspondence;
        $fromUser = $this->disposition->fromUser;
        $toUser = $this->disposition->toUser;

        $message = match($this->action) {
            'created' => "Anda menerima disposisi baru dari {$fromUser->name}",
            'completed' => "Disposisi yang Anda berikan kepada {$toUser->name} telah diselesaikan",
            'updated' => "Disposisi telah diperbarui oleh {$fromUser->name}",
            default => 'Ada perubahan pada disposisi',
        };

        return [
            'type' => 'disposition',
            'action' => $this->action,
            'disposition_id' => $this->disposition->id,
            'correspondence_id' => $correspondence->id,
            'correspondence_subject' => $correspondence->subject,
            'correspondence_number' => $correspondence->letter_number ?: $correspondence->reference_number,
            'from_user_id' => $fromUser->id,
            'from_user_name' => $fromUser->name,
            'to_user_id' => $toUser->id,
            'to_user_name' => $toUser->name,
            'instruction' => $this->disposition->instruction,
            'message' => $message,
            'created_at' => now()->toISOString(),
        ];
    }
}

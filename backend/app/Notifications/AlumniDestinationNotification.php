<?php

namespace App\Notifications;

use App\Models\AlumniDestination;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AlumniDestinationNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $action  proposed|approved|rejected
     */
    public function __construct(
        public AlumniDestination $destination,
        public string $action,
        public string $studentName,
        public string $schoolName
    ) {}

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
        $message = match ($this->action) {
            'proposed' => "Alumni {$this->studentName} terdeteksi terdaftar di {$this->schoolName}. Destinasi menunggu persetujuan Anda.",
            'approved' => "Destinasi alumni {$this->studentName} ke {$this->schoolName} telah disetujui.",
            'rejected' => "Destinasi alumni {$this->studentName} ke {$this->schoolName} telah ditolak.",
            default => 'Ada perubahan pada destinasi alumni.',
        };

        return [
            'type' => 'alumni_destination',
            'action' => $this->action,
            'destination_id' => $this->destination->id,
            'student_id' => $this->destination->student_id,
            'student_name' => $this->studentName,
            'school_name' => $this->schoolName,
            'message' => $message,
            'link' => '/alumni',
            'created_at' => now()->toISOString(),
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\PpdbApplicant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PpdbRegistrationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PpdbApplicant $applicant
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ppdb_registration',
            'action' => 'new_registration',
            'applicant_id' => $this->applicant->id,
            'registration_number' => $this->applicant->registration_number,
            'applicant_name' => $this->applicant->name,
            'period_name' => $this->applicant->period?->name,
            'channel_name' => $this->applicant->channel?->name,
            'message' => "Pendaftaran PPDB baru: {$this->applicant->name} ({$this->applicant->registration_number})",
            'created_at' => now()->toISOString(),
        ];
    }
}

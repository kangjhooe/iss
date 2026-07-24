<?php

namespace App\Notifications;

use App\Models\PpdbApplicant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PpdbRegistrationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $action  new_registration|re_registration
     */
    public function __construct(
        public PpdbApplicant $applicant,
        public string $action = 'new_registration'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $name = $this->applicant->name;
        $reg = $this->applicant->registration_number;

        $message = $this->action === 're_registration'
            ? "Konfirmasi daftar ulang PPDB: {$name} ({$reg})"
            : "Pendaftaran PPDB baru: {$name} ({$reg})";

        return [
            'type' => 'ppdb_registration',
            'action' => $this->action,
            'applicant_id' => $this->applicant->id,
            'registration_number' => $reg,
            'applicant_name' => $name,
            'period_name' => $this->applicant->period?->name,
            'channel_name' => $this->applicant->channel?->name,
            'message' => $message,
            'link' => '/ppdb/pendaftar/' . $this->applicant->id,
            'created_at' => now()->toISOString(),
        ];
    }
}

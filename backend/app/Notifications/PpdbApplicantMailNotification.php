<?php

namespace App\Notifications;

use App\Models\PpdbApplicant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PpdbApplicantMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $action  registered|result
     */
    public function __construct(
        public PpdbApplicant $applicant,
        public string $action = 'registered'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->applicant;
        $a->loadMissing(['period.institution', 'channel']);
        $school = $a->period?->institution?->name ?? 'Sekolah';
        $period = $a->period?->name ?? '—';
        $reg = $a->registration_number ?? '—';

        if ($this->action === 'result') {
            $statusLabel = match ($a->status) {
                'passed' => 'Lulus',
                'reserve' => 'Cadangan',
                'failed' => 'Tidak lulus',
                default => $a->status,
            };

            $mail = (new MailMessage)
                ->subject("Hasil seleksi PPDB — {$school}")
                ->greeting('Halo ' . ($a->name ?? 'Calon peserta') . ',')
                ->line("Hasil seleksi PPDB Anda di {$school} sudah diumumkan.")
                ->line("Nomor pendaftaran: {$reg}")
                ->line("Periode: {$period}")
                ->line("Hasil: {$statusLabel}");

            if (in_array($a->status, ['passed', 'reserve'], true)) {
                $mail->line('Silakan cek hasil dan konfirmasi daftar ulang melalui halaman cek hasil PPDB sekolah.');
            } else {
                $mail->line('Terima kasih atas partisipasi Anda.');
            }

            return $mail->salutation('Hormat kami, ' . $school);
        }

        return (new MailMessage)
            ->subject("Pendaftaran PPDB berhasil — {$school}")
            ->greeting('Halo ' . ($a->name ?? 'Calon peserta') . ',')
            ->line("Pendaftaran PPDB Anda di {$school} telah berhasil diterima.")
            ->line("Nomor pendaftaran: {$reg}")
            ->line("Periode: {$period}")
            ->line('Simpan nomor pendaftaran ini untuk cek hasil dan melengkapi berkas.')
            ->salutation('Hormat kami, ' . $school);
    }
}

<?php

namespace App\Notifications;

use App\Models\StudentMutation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class StudentMutationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param string $action 'requested' | 'approved' | 'rejected'
     */
    public function __construct(
        public StudentMutation $mutation,
        public string $action
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
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $m = $this->mutation;
        $studentName = $m->relationLoaded('student') ? $m->student->name : '-';
        $originName = $m->relationLoaded('originInstitution') ? $m->originInstitution->name : '-';
        $targetName = $m->relationLoaded('targetInstitution') ? $m->targetInstitution->name : '-';
        $requesterName = $m->relationLoaded('requester') ? $m->requester->name : '-';

        $message = match ($this->action) {
            'requested' => $m->initiated_by === 'origin'
                ? "Permohonan mutasi masuk: {$studentName} dari {$originName}. Diajukan oleh {$requesterName}."
                : "Permohonan tarik siswa: {$studentName} dari {$originName} ke sekolah Anda. Diajukan oleh {$requesterName}.",
            'approved' => "Permohonan mutasi siswa {$studentName} telah disetujui. Data siswa telah dipindahkan ke {$targetName}.",
            'rejected' => "Permohonan mutasi siswa {$studentName} telah ditolak.",
            default => 'Ada perubahan pada permohonan mutasi.',
        };

        return [
            'type' => 'student_mutation',
            'action' => $this->action,
            'mutation_id' => $m->id,
            'student_name' => $studentName,
            'student_nisn' => $m->relationLoaded('student') ? $m->student->nisn : null,
            'origin_institution_name' => $originName,
            'target_institution_name' => $targetName,
            'requester_name' => $requesterName,
            'message' => $message,
            'created_at' => now()->toISOString(),
        ];
    }
}

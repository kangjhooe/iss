<?php

namespace App\Notifications;

use App\Models\TeacherMutation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TeacherMutationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param string $action 'requested' | 'approved' | 'rejected' | 'cancelled' | 'cancel_requested' | 'cancel_rejected'
     */
    public function __construct(
        public TeacherMutation $mutation,
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
        $teacherName = $m->relationLoaded('employee') ? $m->employee->name : '-';
        $originName = $m->relationLoaded('originInstitution') ? $m->originInstitution->name : '-';
        $targetName = $m->relationLoaded('targetInstitution') ? $m->targetInstitution->name : '-';
        $requesterName = $m->relationLoaded('requester') ? $m->requester->name : '-';

        $message = match ($this->action) {
            'requested' => $m->initiated_by === 'origin'
                ? "Permohonan mutasi masuk: Guru {$teacherName} dari {$originName}. Diajukan oleh {$requesterName}."
                : "Permohonan tarik guru: {$teacherName} dari {$originName} ke sekolah Anda. Diajukan oleh {$requesterName}.",
            'approved' => "Permohonan mutasi guru {$teacherName} telah disetujui. Data guru telah dipindahkan ke {$targetName}.",
            'rejected' => "Permohonan mutasi guru {$teacherName} telah ditolak.",
            'cancelled' => "Permohonan mutasi guru {$teacherName} telah dibatalkan.",
            'cancel_requested' => "Permohonan pembatalan mutasi: Guru {$teacherName} dari {$originName}. Menunggu persetujuan sekolah tujuan.",
            'cancel_rejected' => "Permohonan pembatalan mutasi guru {$teacherName} ditolak oleh sekolah tujuan. Mutasi tetap berlaku.",
            default => 'Ada perubahan pada permohonan mutasi guru.',
        };

        return [
            'type' => 'teacher_mutation',
            'action' => $this->action,
            'mutation_id' => $m->id,
            'teacher_name' => $teacherName,
            'teacher_nuptk' => $m->relationLoaded('employee') ? $m->employee->nuptk : null,
            'origin_institution_name' => $originName,
            'target_institution_name' => $targetName,
            'requester_name' => $requesterName,
            'message' => $message,
            'created_at' => now()->toISOString(),
        ];
    }
}

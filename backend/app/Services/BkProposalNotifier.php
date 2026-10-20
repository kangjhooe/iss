<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\User;
use App\Models\Violation;
use App\Notifications\BkProposalNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class BkProposalNotifier
{
    /**
     * Guru BK dan admin sekolah, selain pengusul.
     */
    public function notifyReviewers(int $institutionId, string $kind, string $message, array $extra = [], ?int $exceptUserId = null): void
    {
        $users = User::query()
            ->where('institution_id', $institutionId)
            ->when($exceptUserId, fn ($query) => $query->where('id', '!=', $exceptUserId))
            ->where(function ($query) {
                $query->whereIn('role', ['institution_admin', 'admin'])
                    ->orWhereHas('permissions', fn ($permissions) => $permissions->where('key', 'violation'));
            })
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        try {
            Notification::send($users, new BkProposalNotification($kind, $message, $extra));
        } catch (\Throwable $e) {
            Log::warning('BK proposal notification failed', [
                'institution_id' => $institutionId,
                'kind' => $kind,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function achievementProposed(Achievement $achievement, User $proposer): void
    {
        $achievement->loadMissing(['student:id,name', 'achievementType:id,name']);
        $student = $achievement->student?->name ?: 'siswa';
        $title = $achievement->title ?: ($achievement->achievementType?->name ?: 'Prestasi');
        $detail = collect([
            $this->levelLabel($achievement->level),
            $this->rankLabel($achievement->rank),
        ])->filter()->implode(', ');
        $message = $detail !== ''
            ? "Usulan prestasi {$title} ({$detail}) untuk {$student}. Diajukan oleh {$proposer->name}."
            : "Usulan prestasi {$title} untuk {$student}. Diajukan oleh {$proposer->name}.";

        $this->notifyReviewers(
            (int) $achievement->institution_id,
            'achievement',
            $message,
            [
                'achievement_id' => $achievement->id,
                'student_name' => $student,
            ],
            (int) $proposer->id
        );
    }

    public function violationProposed(Violation $violation, User $proposer): void
    {
        $violation->loadMissing(['student:id,name', 'violationType:id,name']);
        $student = $violation->student?->name ?: 'siswa';
        $type = $violation->violationType?->name ?: 'Pelanggaran';
        $message = "Usulan pelanggaran {$type} untuk {$student}. Diajukan oleh {$proposer->name}.";

        $this->notifyReviewers(
            (int) $violation->institution_id,
            'violation',
            $message,
            [
                'violation_id' => $violation->id,
                'student_name' => $student,
            ],
            (int) $proposer->id
        );
    }

    private function levelLabel(?string $level): ?string
    {
        return match ($level) {
            'sekolah' => 'Sekolah',
            'kabupaten' => 'Kabupaten/Kota',
            'provinsi' => 'Provinsi',
            'nasional' => 'Nasional',
            'internasional' => 'Internasional',
            default => null,
        };
    }

    private function rankLabel(?string $rank): ?string
    {
        return match ($rank) {
            'juara_1' => 'Juara 1',
            'juara_2' => 'Juara 2',
            'juara_3' => 'Juara 3',
            'finalis' => 'Finalis',
            'peserta' => 'Peserta',
            'lainnya' => 'Lainnya',
            default => null,
        };
    }
}

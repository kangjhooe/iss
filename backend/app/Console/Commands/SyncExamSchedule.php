<?php

namespace App\Console\Commands;

use App\Models\ExamSession;
use App\Services\ExamService;
use Illuminate\Console\Command;

class SyncExamSchedule extends Command
{
    protected $signature = 'exam:sync-schedule';

    protected $description = 'Mulai sesi yang jadwalnya sudah tiba dan akhiri sesi yang melewati jadwal selesai';

    public function handle(ExamService $examService): int
    {
        $now = now();
        $started = 0;
        $ended = 0;

        $due = ExamSession::query()
            ->whereIn('status', [ExamSession::STATUS_DRAFT, ExamSession::STATUS_SCHEDULED])
            ->whereNotNull('scheduled_start_at')
            ->where('scheduled_start_at', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('scheduled_end_at')
                    ->orWhere('scheduled_end_at', '>', $now);
            })
            ->get();

        foreach ($due as $session) {
            $examService->startSession($session);
            $started++;
        }

        $over = ExamSession::query()
            ->where('status', ExamSession::STATUS_STARTED)
            ->whereNotNull('scheduled_end_at')
            ->where('scheduled_end_at', '<=', $now)
            ->get();

        foreach ($over as $session) {
            $examService->endSession($session, true);
            $ended++;
        }

        if ($started || $ended) {
            $this->info("Dimulai: {$started}. Diakhiri: {$ended}.");
        }

        return 0;
    }
}

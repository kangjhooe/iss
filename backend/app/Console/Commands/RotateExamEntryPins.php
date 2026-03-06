<?php

namespace App\Console\Commands;

use App\Models\ExamSession;
use App\Services\ExamService;
use Illuminate\Console\Command;

class RotateExamEntryPins extends Command
{
    protected $signature = 'exam:rotate-entry-pins';

    protected $description = 'Rotate entry PIN (kode ujian) for started sessions every 20 minutes';

    public function handle(ExamService $examService): int
    {
        $cutoff = now()->subMinutes(20);
        $sessions = ExamSession::where('status', ExamSession::STATUS_STARTED)
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('entry_pin_updated_at')
                    ->orWhere('entry_pin_updated_at', '<', $cutoff);
            })
            ->get();

        if ($sessions->isEmpty()) {
            return 0;
        }

        foreach ($sessions as $session) {
            $examService->regenerateEntryPin($session);
            $this->info("PIN rotated for session: {$session->name} (ID: {$session->id})");
        }

        $this->info('Done. Rotated ' . $sessions->count() . ' session(s).');
        return 0;
    }
}

<?php

namespace App\Services;

use App\Models\ExamAnswer;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\ExamSession;

class ExamService
{
    /**
     * Start session (manual or by schedule). Sets status to started, started_at = now, generates entry_pin.
     */
    public function startSession(ExamSession $session): ExamSession
    {
        if ($session->status === ExamSession::STATUS_STARTED) {
            return $session;
        }
        $pin = ExamSession::generateEntryPin();
        $session->update([
            'status' => ExamSession::STATUS_STARTED,
            'started_at' => $session->started_at ?? now(),
            'entry_pin' => $pin,
            'entry_pin_updated_at' => now(),
        ]);
        return $session->fresh();
    }

    /**
     * Regenerate entry PIN for session (e.g. every 20 minutes). Returns new PIN.
     */
    public function regenerateEntryPin(ExamSession $session): string
    {
        $pin = ExamSession::generateEntryPin();
        $session->update([
            'entry_pin' => $pin,
            'entry_pin_updated_at' => now(),
        ]);
        return $pin;
    }

    /**
     * End session. Sets status to ended, ended_at = now. Optionally compute auto scores for all submitted.
     */
    public function endSession(ExamSession $session, bool $computeScores = true): ExamSession
    {
        if ($session->status === ExamSession::STATUS_ENDED) {
            return $session;
        }
        $session->update([
            'status' => ExamSession::STATUS_ENDED,
            'ended_at' => now(),
        ]);
        if ($computeScores) {
            $this->computeAutoScoresForSession($session);
        }
        return $session->fresh();
    }

    /**
     * Reset session to draft: clear started_at/ended_at, set status draft. Optionally reset all participants.
     */
    public function resetSession(ExamSession $session, bool $resetParticipants = true): ExamSession
    {
        if ($resetParticipants) {
            ExamAnswer::whereIn('exam_participant_id', $session->participants()->pluck('id'))->delete();
            $session->participants()->update([
                'question_order' => null,
                'started_at' => null,
                'submitted_at' => null,
                'score' => null,
                'score_max' => null,
                'status' => ExamParticipant::STATUS_REGISTERED,
            ]);
        }
        $session->update([
            'status' => ExamSession::STATUS_DRAFT,
            'started_at' => null,
            'ended_at' => null,
            'entry_pin' => null,
            'entry_pin_updated_at' => null,
        ]);
        return $session->fresh();
    }

    /**
     * Reset one participant: clear answers, set status to registered. Mereka bisa masuk lagi dengan token/kode ujian.
     */
    public function resetParticipant(ExamParticipant $participant): ExamParticipant
    {
        ExamAnswer::where('exam_participant_id', $participant->id)->delete();
        $participant->update([
            'question_order' => null,
            'started_at' => null,
            'submitted_at' => null,
            'score' => null,
            'score_max' => null,
            'score_released' => false,
            'score_released_at' => null,
            'status' => ExamParticipant::STATUS_REGISTERED,
        ]);
        return $participant->fresh();
    }

    /**
     * Build question order for participant and mark as started.
     */
    public function startParticipant(ExamParticipant $participant): ExamParticipant
    {
        if ($participant->status !== ExamParticipant::STATUS_REGISTERED) {
            return $participant;
        }
        $exam = $participant->examSession->exam;
        $questionIds = ExamQuestion::where('exam_id', $exam->id)->orderBy('sort_order')->pluck('question_bank_id')->toArray();
        if ($exam->shuffle_questions && count($questionIds) > 0) {
            shuffle($questionIds);
        }
        $participant->update([
            'question_order' => $questionIds,
            'started_at' => now(),
            'status' => ExamParticipant::STATUS_STARTED,
        ]);
        return $participant->fresh();
    }

    /**
     * Compute auto score for PG and isian for a participant; uraian remains null until manual.
     */
    public function computeAutoScoreForParticipant(ExamParticipant $participant): void
    {
        $participant->loadMissing(['answers.questionBank.options', 'examSession.exam.examQuestions']);
        $byQuestionId = $participant->examSession->exam->examQuestions->keyBy('question_bank_id');

        $total = 0;
        $max = 0;
        foreach ($participant->answers as $answer) {
            $eq = $byQuestionId->get($answer->question_bank_id);
            $snapshot = $eq
                ? $eq->scoringPayload($answer->questionBank)
                : ($answer->questionBank ? ExamQuestionSnapshot::capture($answer->questionBank) : null);
            if (! $snapshot) {
                continue;
            }
            $max += (float) ($snapshot['weight'] ?? 0);
            $total += ExamQuestionSnapshot::applyAutoScore($answer, $snapshot);
        }
        $participant->update([
            'score' => $total,
            'score_max' => $max,
        ]);
    }

    /**
     * Compute auto scores for all submitted participants in session.
     */
    public function computeAutoScoresForSession(ExamSession $session): void
    {
        $participants = $session->participants()->where('status', ExamParticipant::STATUS_SUBMITTED)->get();
        foreach ($participants as $p) {
            $this->computeAutoScoreForParticipant($p);
        }
    }

    /**
     * Release score to participant (visible to student).
     */
    public function releaseScore(ExamParticipant $participant): ExamParticipant
    {
        $participant->update([
            'score_released' => true,
            'score_released_at' => now(),
        ]);
        return $participant->fresh();
    }

    /**
     * Release scores for all submitted participants in a session that already have a score.
     */
    public function releaseAllScores(ExamSession $session): int
    {
        $participants = $session->participants()
            ->where('status', ExamParticipant::STATUS_SUBMITTED)
            ->where('score_released', false)
            ->whereNotNull('score')
            ->get();

        $count = 0;
        foreach ($participants as $participant) {
            $this->releaseScore($participant);
            $count++;
        }

        return $count;
    }

    /**
     * Live monitoring counts for a session (registered / in progress / submitted).
     *
     * @return array{total:int,registered:int,started:int,submitted:int,score_released:int,avg_score:float|null}
     */
    public function monitorSummary(ExamSession $session): array
    {
        $participants = $session->participants()->get(['status', 'score', 'score_max', 'score_released']);
        $submitted = $participants->where('status', ExamParticipant::STATUS_SUBMITTED);
        $withScore = $submitted->whereNotNull('score');

        return [
            'total' => $participants->count(),
            'registered' => $participants->where('status', ExamParticipant::STATUS_REGISTERED)->count(),
            'started' => $participants->where('status', ExamParticipant::STATUS_STARTED)->count(),
            'submitted' => $submitted->count(),
            'score_released' => $participants->where('score_released', true)->count(),
            'avg_score' => $withScore->count() > 0
                ? round((float) $withScore->avg('score'), 2)
                : null,
        ];
    }

    /**
     * Recompute participant total score from all answers (PG/isian already scored, uraian manual).
     */
    public function recomputeParticipantScore(ExamParticipant $participant): void
    {
        $participant->loadMissing(['answers.questionBank.options', 'examSession.exam.examQuestions']);
        $byQuestionId = $participant->examSession->exam->examQuestions->keyBy('question_bank_id');

        $total = 0;
        $max = 0;
        foreach ($participant->answers as $answer) {
            $eq = $byQuestionId->get($answer->question_bank_id);
            $snapshot = $eq
                ? $eq->scoringPayload($answer->questionBank)
                : ($answer->questionBank ? ExamQuestionSnapshot::capture($answer->questionBank) : null);
            if ($snapshot) {
                $max += (float) ($snapshot['weight'] ?? 0);
            }
            $score = $answer->score !== null ? (float) $answer->score : 0;
            $total += $score;
        }
        $participant->update([
            'score' => $total,
            'score_max' => $max,
        ]);
    }

    /**
     * Get question at 0-based index for participant (with options shuffled if exam.shuffle_options).
     */
    public function getQuestionAt(ExamParticipant $participant, int $index): ?array
    {
        $order = $participant->question_order;
        if (!$order || $index < 0 || $index >= count($order)) {
            return null;
        }
        $questionBankId = (int) $order[$index];
        $exam = $participant->examSession->exam;
        $examQuestion = ExamQuestion::query()
            ->where('exam_id', $exam->id)
            ->where('question_bank_id', $questionBankId)
            ->with(['questionBank.options', 'questionBank.stimulus', 'questionBank.bankSoal'])
            ->first();

        $snapshot = $examQuestion?->scoringPayload($examQuestion?->questionBank);
        if (! $snapshot) {
            return null;
        }

        $saved = ExamAnswer::where('exam_participant_id', $participant->id)
            ->where('question_bank_id', $questionBankId)
            ->first();

        return ExamQuestionSnapshot::toAttemptPayload(
            $snapshot,
            $index,
            count($order),
            (bool) $exam->shuffle_options,
            $saved ? [
                'answer_text' => $saved->answer_text,
                'question_option_id' => $saved->question_option_id,
                'selected_option_ids' => $saved->selected_option_ids,
                'matching_answer' => $saved->matching_answer,
            ] : null
        );
    }
}

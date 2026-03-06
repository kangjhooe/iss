<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\QuestionBank;
use Illuminate\Support\Facades\DB;

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
        $total = 0;
        $max = 0;
        foreach ($participant->answers as $answer) {
            $q = $answer->questionBank;
            $max += (float) $q->weight;
            if ($q->type === QuestionBank::TYPE_PG) {
                $correct = $answer->question_option_id && $q->options()->where('id', $answer->question_option_id)->where('is_correct', true)->exists();
                $total += $correct ? (float) $q->weight : 0;
                if ($correct) {
                    $answer->update(['score' => $q->weight, 'saved_at' => $answer->saved_at ?? now()]);
                } else {
                    $answer->update(['score' => 0, 'saved_at' => $answer->saved_at ?? now()]);
                }
            } elseif ($q->type === QuestionBank::TYPE_PG_KOMPLEKS) {
                $options = $q->options->keyBy('id');
                $selectedIds = $answer->selected_option_ids ?? [];
                if (is_array($selectedIds)) {
                    $selectedIds = array_map('intval', $selectedIds);
                } else {
                    $selectedIds = [];
                }
                $score = 0;
                foreach ($selectedIds as $optId) {
                    $opt = $options->get($optId);
                    if ($opt && $opt->is_correct) {
                        $score += (float) $opt->option_weight;
                    }
                }
                $total += $score;
                $answer->update([
                    'score' => $score,
                    'saved_at' => $answer->saved_at ?? now(),
                ]);
            } elseif ($q->type === QuestionBank::TYPE_MATCHING) {
                $matchingData = $q->matching_data ?? [];
                $correctPairs = isset($matchingData['correct']) ? $matchingData['correct'] : [];
                $studentPairs = $answer->matching_answer ?? [];
                $correctCount = 0;
                foreach ($correctPairs as $pair) {
                    $l = (string) ($pair['left_id'] ?? $pair['l'] ?? '');
                    $r = (string) ($pair['right_id'] ?? $pair['r'] ?? '');
                    foreach ($studentPairs as $sp) {
                        $sl = (string) ($sp['left_id'] ?? $sp['l'] ?? '');
                        $sr = (string) ($sp['right_id'] ?? $sp['r'] ?? '');
                        if ($sl === $l && $sr === $r) {
                            $correctCount++;
                            break;
                        }
                    }
                }
                $totalPairs = count($correctPairs);
                $score = $totalPairs > 0 ? (float) $q->weight * ($correctCount / $totalPairs) : 0;
                $total += $score;
                $answer->update([
                    'score' => $score,
                    'saved_at' => $answer->saved_at ?? now(),
                ]);
            } elseif ($q->type === QuestionBank::TYPE_ISIAN && $q->key_answer !== null) {
                $trimKey = trim(mb_strtolower($q->key_answer));
                $trimAnswer = trim(mb_strtolower($answer->answer_text ?? ''));
                $correct = $trimKey === $trimAnswer;
                $total += $correct ? (float) $q->weight : 0;
                $answer->update([
                    'score' => $correct ? $q->weight : 0,
                    'saved_at' => $answer->saved_at ?? now(),
                ]);
            }
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
     * Recompute participant total score from all answers (PG/isian already scored, uraian manual).
     */
    public function recomputeParticipantScore(ExamParticipant $participant): void
    {
        $total = 0;
        $max = 0;
        foreach ($participant->answers as $answer) {
            $q = $answer->questionBank;
            $max += (float) $q->weight;
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
        $q = QuestionBank::with(['stimulus', 'options'])->find($questionBankId);
        if (!$q) {
            return null;
        }
        $options = $q->options->toArray();
        $shuffleOptions = $exam->shuffle_options && in_array($q->type, [QuestionBank::TYPE_PG, QuestionBank::TYPE_PG_KOMPLEKS], true) && count($options) > 0;
        if ($shuffleOptions) {
            shuffle($options);
        }
        $saved = ExamAnswer::where('exam_participant_id', $participant->id)
            ->where('question_bank_id', $questionBankId)
            ->first();

        $payload = [
            'question_bank_id' => $q->id,
            'type' => $q->type,
            'body' => $q->body,
            'weight' => (float) $q->weight,
            'stimulus' => $q->stimulus ? [
                'id' => $q->stimulus->id,
                'content' => $q->stimulus->content,
                'type' => $q->stimulus->type,
            ] : null,
            'index' => $index,
            'total' => count($order),
            'saved_answer_text' => $saved ? $saved->answer_text : null,
            'saved_question_option_id' => $saved ? $saved->question_option_id : null,
            'saved_selected_option_ids' => $saved && $saved->selected_option_ids ? $saved->selected_option_ids : null,
            'saved_matching_answer' => $saved && $saved->matching_answer ? $saved->matching_answer : null,
        ];

        if ($q->type === QuestionBank::TYPE_MATCHING) {
            $matching = $q->matching_data ?? ['left' => [], 'right' => []];
            $left = $matching['left'] ?? [];
            $right = $matching['right'] ?? [];
            if ($exam->shuffle_options && count($right) > 0) {
                shuffle($right);
            }
            $payload['matching_left'] = $left;
            $payload['matching_right'] = $right;
            $payload['options'] = [];
        } else {
            $payload['options'] = $options;
        }

        return $payload;
    }
}

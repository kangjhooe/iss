<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\QuestionBank;
use App\Models\SubjectKkm;

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
        if ($session->status === ExamSession::STATUS_ENDED) {
            throw new \InvalidArgumentException('Sesi yang sudah selesai harus direset ke draf sebelum dimulai lagi.');
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
        $this->collectOpenParticipants($session);
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
                'flagged_question_ids' => null,
                'started_at' => null,
                'extra_seconds' => 0,
                'last_seen_at' => null,
                'attempt_number' => 1,
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
            'flagged_question_ids' => null,
            'started_at' => null,
            'extra_seconds' => 0,
            'last_seen_at' => null,
            'attempt_number' => 1,
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
     * When shuffle is on, units (stimulus sets / single questions) are shuffled;
     * soal dengan stimulus yang sama tetap berurutan di dalam set.
     */
    public function startParticipant(ExamParticipant $participant): ExamParticipant
    {
        if ($participant->status !== ExamParticipant::STATUS_REGISTERED) {
            return $participant;
        }
        $exam = $participant->examSession->exam;
        $examQuestions = ExamQuestion::where('exam_id', $exam->id)
            ->orderBy('sort_order')
            ->get(['question_bank_id', 'snapshot']);
        $questionIds = $examQuestions->pluck('question_bank_id')->map(fn ($id) => (int) $id)->values()->all();
        if ($exam->shuffle_questions && count($questionIds) > 0) {
            $stimulusByQid = [];
            foreach ($examQuestions as $eq) {
                $snap = is_array($eq->snapshot) ? $eq->snapshot : [];
                $sid = $snap['stimulus']['id'] ?? null;
                $stimulusByQid[(int) $eq->question_bank_id] = $sid !== null ? (int) $sid : null;
            }
            $questionIds = self::shuffleKeepingStimulusGroups($questionIds, $stimulusByQid);
        }
        $participant->update([
            'question_order' => $questionIds,
            'flagged_question_ids' => [],
            'started_at' => now(),
            'extra_seconds' => 0,
            'status' => ExamParticipant::STATUS_STARTED,
        ]);
        return $participant->fresh();
    }

    /**
     * Acak urutan unit soal; soal se-stimulus tetap digandeng berurutan.
     *
     * @param  list<int>  $questionIds
     * @param  array<int, int|null>  $stimulusByQid  question_bank_id => stimulus_id|null
     * @return list<int>
     */
    public static function shuffleKeepingStimulusGroups(array $questionIds, array $stimulusByQid): array
    {
        $units = [];
        $n = count($questionIds);
        $i = 0;
        while ($i < $n) {
            $id = (int) $questionIds[$i];
            $sid = $stimulusByQid[$id] ?? null;
            if ($sid) {
                $group = [$id];
                $j = $i + 1;
                while ($j < $n) {
                    $nextId = (int) $questionIds[$j];
                    $nextSid = $stimulusByQid[$nextId] ?? null;
                    if ($nextSid !== $sid) {
                        break;
                    }
                    $group[] = $nextId;
                    $j++;
                }
                $units[] = $group;
                $i = $j;
            } else {
                $units[] = [$id];
                $i++;
            }
        }
        shuffle($units);
        $out = [];
        foreach ($units as $unit) {
            foreach ($unit as $qid) {
                $out[] = $qid;
            }
        }

        return $out;
    }

    /**
     * Rapikan daftar ID: soal dengan stimulus sama digabung berurutan (urut sort_order bank).
     *
     * @param  list<int>  $questionIds
     * @param  \Illuminate\Support\Collection<int, \App\Models\QuestionBank>  $questionsById
     * @return list<int>
     */
    public static function orderKeepingStimulusGroups(array $questionIds, $questionsById): array
    {
        $ids = array_values(array_unique(array_map('intval', $questionIds)));
        $used = [];
        $result = [];

        foreach ($ids as $id) {
            if (isset($used[$id])) {
                continue;
            }
            $q = $questionsById->get($id);
            $sid = $q?->stimulus_id;
            if (! $sid) {
                $result[] = $id;
                $used[$id] = true;
                continue;
            }
            $group = [];
            foreach ($ids as $id2) {
                if (isset($used[$id2])) {
                    continue;
                }
                $q2 = $questionsById->get($id2);
                if ($q2 && (int) $q2->stimulus_id === (int) $sid) {
                    $group[] = $id2;
                    $used[$id2] = true;
                }
            }
            usort($group, function ($a, $b) use ($questionsById) {
                $qa = $questionsById->get($a);
                $qb = $questionsById->get($b);
                $sa = (int) ($qa->sort_order ?? 0);
                $sb = (int) ($qb->sort_order ?? 0);
                if ($sa !== $sb) {
                    return $sa <=> $sb;
                }

                return $a <=> $b;
            });
            foreach ($group as $gid) {
                $result[] = $gid;
            }
        }

        return $result;
    }

    /**
     * Compute auto score for PG and isian for a participant; uraian remains null until manual.
     * Total disimpan dalam skala 0–100 (bobot relatif soal dinormalisasi).
     */
    public function computeAutoScoreForParticipant(ExamParticipant $participant): void
    {
        $scaled = $this->totalsForParticipant($participant, true);
        $participant->update([
            'score' => $scaled['score'],
            'score_max' => $scaled['score_max'],
        ]);
    }

    /**
     * Normalisasi skor mentah (jumlah bobot) ke skala 0–100.
     *
     * @return array{score: float, score_max: float}
     */
    public function scaleScoreToHundred(float $rawTotal, float $rawMax): array
    {
        if ($rawMax <= 0) {
            return ['score' => 0.0, 'score_max' => 100.0];
        }

        return [
            'score' => round(($rawTotal / $rawMax) * 100, 2),
            'score_max' => 100.0,
        ];
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
     * Total disimpan dalam skala 0–100.
     */
    public function recomputeParticipantScore(ExamParticipant $participant): void
    {
        $scaled = $this->totalsForParticipant($participant, false);
        $participant->update([
            'score' => $scaled['score'],
            'score_max' => $scaled['score_max'],
        ]);
    }

    /**
     * Skor mentah dari seluruh soal di urutan peserta. Soal kosong tetap masuk penyebut.
     * Uraian memakai nilai yang sudah dikoreksi; hitung ulang otomatis tidak menimpa uraian.
     *
     * @return array{score: float, score_max: float}
     */
    private function totalsForParticipant(ExamParticipant $participant, bool $rescoreObjective): array
    {
        $participant->loadMissing(['answers', 'examSession.exam.examQuestions.questionBank']);
        $examQuestions = $participant->examSession->exam->examQuestions->keyBy(
            fn ($eq) => (int) $eq->question_bank_id
        );
        $answers = $participant->answers->keyBy(fn ($a) => (int) $a->question_bank_id);

        $order = array_map('intval', $participant->question_order ?? []);
        if ($order === []) {
            $order = $examQuestions->keys()->map(fn ($id) => (int) $id)->values()->all();
        }

        $total = 0.0;
        $max = 0.0;
        foreach ($order as $qid) {
            $eq = $examQuestions->get($qid);
            if (! $eq) {
                continue;
            }
            $answer = $answers->get($qid);
            $snapshot = $eq->scoringPayload($answer?->questionBank ?? $eq->questionBank);
            if (! $snapshot) {
                continue;
            }
            $max += (float) ($snapshot['weight'] ?? 0);
            $type = (string) ($snapshot['type'] ?? '');
            if ($type === QuestionBank::TYPE_URAIAN) {
                $total += $answer && $answer->score !== null ? (float) $answer->score : 0.0;

                continue;
            }
            if (! $answer) {
                continue;
            }
            if ($rescoreObjective) {
                $total += ExamQuestionSnapshot::applyAutoScore($answer, $snapshot);
            } else {
                $total += $answer->score !== null ? (float) $answer->score : 0.0;
            }
        }

        return $this->scaleScoreToHundred($total, $max);
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

        $stimulusContinued = false;
        $currentStimulusId = $snapshot['stimulus']['id'] ?? null;
        if ($index > 0 && $currentStimulusId) {
            $prevQid = (int) $order[$index - 1];
            $prevEq = ExamQuestion::query()
                ->where('exam_id', $exam->id)
                ->where('question_bank_id', $prevQid)
                ->first();
            $prevSnap = $prevEq?->scoringPayload($prevEq->questionBank);
            $prevStimulusId = $prevSnap['stimulus']['id'] ?? null;
            $stimulusContinued = $prevStimulusId !== null && (int) $prevStimulusId === (int) $currentStimulusId;
        }

        $payload = ExamQuestionSnapshot::toAttemptPayload(
            $snapshot,
            $index,
            count($order),
            (bool) $exam->shuffle_options,
            $saved ? [
                'answer_text' => $saved->answer_text,
                'question_option_id' => $saved->question_option_id,
                'selected_option_ids' => $saved->selected_option_ids,
                'matching_answer' => $saved->matching_answer,
            ] : null,
            $participant->id.':'.$questionBankId
        );
        $payload['stimulus_continued'] = $stimulusContinued;

        return $payload;
    }

    /**
     * Sisa detik dari started_at + durasi paket + waktu tambahan pengawas.
     * Null bila durasi tidak diatur atau peserta belum mulai.
     */
    public function secondsRemaining(ExamParticipant $participant): ?int
    {
        if ($participant->status !== ExamParticipant::STATUS_STARTED || ! $participant->started_at) {
            return null;
        }
        $participant->loadMissing('examSession.exam');
        $minutes = (int) ($participant->examSession->exam->duration_minutes ?? 0);
        if ($minutes <= 0) {
            return null;
        }
        $deadline = $participant->started_at->copy()
            ->addMinutes($minutes)
            ->addSeconds((int) ($participant->extra_seconds ?? 0));

        return max(0, $deadline->getTimestamp() - now()->getTimestamp());
    }

    public function isPastDeadline(ExamParticipant $participant): bool
    {
        $remaining = $this->secondsRemaining($participant);

        return $remaining !== null && $remaining <= 0;
    }

    /**
     * Kumpulkan peserta yang masih mengerjakan (waktu habis atau sesi ditutup).
     */
    public function submitParticipant(ExamParticipant $participant): ExamParticipant
    {
        if ($participant->status === ExamParticipant::STATUS_SUBMITTED) {
            return $participant;
        }
        if ($participant->status !== ExamParticipant::STATUS_STARTED) {
            return $participant;
        }
        $participant->update([
            'status' => ExamParticipant::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
        $fresh = $participant->fresh();
        $this->computeAutoScoreForParticipant($fresh);
        $fresh = $fresh->fresh();
        $fresh->loadMissing(['answers', 'examSession.exam.examQuestions.questionBank']);
        if ($fresh->examSession->exam->isPractice() && ! $this->hasUnscoredEssay($fresh)) {
            $this->releaseScore($fresh);
            $fresh = $fresh->fresh();
        }

        return $fresh;
    }

    /**
     * Latihan tidak merilis nilai selama ada uraian yang belum dikoreksi.
     */
    private function hasUnscoredEssay(ExamParticipant $participant): bool
    {
        $participant->loadMissing(['answers', 'examSession.exam.examQuestions.questionBank']);
        $answers = $participant->answers->keyBy(fn ($a) => (int) $a->question_bank_id);
        foreach ($participant->examSession->exam->examQuestions as $eq) {
            $snapshot = $eq->scoringPayload($eq->questionBank);
            if (! $snapshot || ($snapshot['type'] ?? '') !== QuestionBank::TYPE_URAIAN) {
                continue;
            }
            $answer = $answers->get((int) $eq->question_bank_id);
            if (! $answer || $answer->score === null) {
                return true;
            }
        }

        return false;
    }

    public function touchPresence(ExamParticipant $participant): void
    {
        if ($participant->status !== ExamParticipant::STATUS_STARTED) {
            return;
        }
        $participant->forceFill(['last_seen_at' => now()])->save();
    }

    public function canRetry(ExamParticipant $participant): bool
    {
        $participant->loadMissing('examSession.exam');
        $exam = $participant->examSession->exam;
        if (! $exam->isPractice()) {
            return false;
        }
        if ($participant->examSession->status !== ExamSession::STATUS_STARTED) {
            return false;
        }
        if ($participant->status !== ExamParticipant::STATUS_SUBMITTED) {
            return false;
        }
        $max = $exam->max_attempts;
        if ($max === null) {
            return true;
        }

        return (int) $participant->attempt_number < (int) $max;
    }

    public function retryPractice(ExamParticipant $participant): ExamParticipant
    {
        if (! $this->canRetry($participant)) {
            throw new \InvalidArgumentException('Percobaan latihan ini tidak bisa diulang.');
        }
        $next = (int) $participant->attempt_number + 1;
        ExamAnswer::where('exam_participant_id', $participant->id)->delete();
        $participant->update([
            'question_order' => null,
            'flagged_question_ids' => null,
            'started_at' => null,
            'extra_seconds' => 0,
            'last_seen_at' => null,
            'submitted_at' => null,
            'score' => null,
            'score_max' => null,
            'score_released' => false,
            'score_released_at' => null,
            'attempt_number' => $next,
            'status' => ExamParticipant::STATUS_REGISTERED,
        ]);

        return $this->startParticipant($participant->fresh());
    }

    public function collectOpenParticipants(ExamSession $session): int
    {
        $open = $session->participants()
            ->where('status', ExamParticipant::STATUS_STARTED)
            ->get();
        foreach ($open as $participant) {
            $this->submitParticipant($participant);
        }

        return $open->count();
    }

    public function extendTime(ExamParticipant $participant, int $minutes): ExamParticipant
    {
        $minutes = max(1, min(60, $minutes));
        $participant->update([
            'extra_seconds' => (int) ($participant->extra_seconds ?? 0) + ($minutes * 60),
        ]);

        return $participant->fresh();
    }

    public static function answerIsFilled(?ExamAnswer $answer): bool
    {
        if (! $answer) {
            return false;
        }
        if ($answer->question_option_id) {
            return true;
        }
        if (is_array($answer->selected_option_ids) && count($answer->selected_option_ids) > 0) {
            return true;
        }
        if (is_array($answer->matching_answer) && count($answer->matching_answer) > 0) {
            return true;
        }

        return trim((string) ($answer->answer_text ?? '')) !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function attemptState(ExamParticipant $participant): array
    {
        $participant->loadMissing(['examSession.exam.subject', 'answers']);
        $exam = $participant->examSession->exam;
        $order = array_map('intval', $participant->question_order ?? []);
        $answers = $participant->answers->keyBy(fn ($a) => (int) $a->question_bank_id);
        $flagged = array_flip(array_map('intval', $participant->flagged_question_ids ?? []));
        $items = [];
        foreach ($order as $index => $qid) {
            $items[] = [
                'index' => $index,
                'question_bank_id' => $qid,
                'answered' => self::answerIsFilled($answers->get($qid)),
                'flagged' => isset($flagged[$qid]),
            ];
        }
        $released = (bool) $participant->score_released;

        return [
            'status' => $participant->status,
            'submitted' => $participant->status === ExamParticipant::STATUS_SUBMITTED,
            'score_released' => $released,
            'score' => $released && $participant->score !== null ? (float) $participant->score : null,
            'score_max' => $released && $participant->score_max !== null ? (float) $participant->score_max : null,
            'attempt_number' => (int) ($participant->attempt_number ?? 1),
            'max_attempts' => $exam->max_attempts !== null ? (int) $exam->max_attempts : null,
            'can_retry' => $this->canRetry($participant),
            'delivery_mode' => $exam->delivery_mode ?: Exam::DELIVERY_PROCTORED,
            'seconds_remaining' => $this->secondsRemaining($participant),
            'total_questions' => count($order),
            'items' => $items,
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'duration_minutes' => $exam->duration_minutes,
                'subject' => $exam->subject ? ['id' => $exam->subject->id, 'name' => $exam->subject->name] : null,
            ],
            'session' => [
                'id' => $participant->exam_session_id,
                'name' => $participant->examSession->name,
            ],
        ];
    }

    public function setQuestionFlag(ExamParticipant $participant, int $questionBankId, bool $flagged): ExamParticipant
    {
        $order = array_map('intval', $participant->question_order ?? []);
        if (! in_array($questionBankId, $order, true)) {
            return $participant;
        }
        $ids = array_map('intval', $participant->flagged_question_ids ?? []);
        $ids = array_values(array_filter($ids, fn ($id) => $id !== $questionBankId));
        if ($flagged) {
            $ids[] = $questionBankId;
        }
        $participant->update(['flagged_question_ids' => $ids]);

        return $participant->fresh();
    }

    /**
     * Pembahasan setelah nilai dirilis. Kunci tidak dikirim sebelum rilis.
     *
     * @return array<string, mixed>
     */
    public function reviewForParticipant(ExamParticipant $participant): array
    {
        $released = (bool) $participant->score_released;
        $payload = [
            'submitted' => $participant->status === ExamParticipant::STATUS_SUBMITTED,
            'released' => $released,
            'score' => $released && $participant->score !== null ? (float) $participant->score : null,
            'score_max' => $released && $participant->score_max !== null ? (float) $participant->score_max : null,
            'message' => $released
                ? null
                : 'Nilai belum dirilis. Pembahasan tampil setelah guru merilis nilai.',
            'questions' => [],
        ];
        if (! $released || $participant->status !== ExamParticipant::STATUS_SUBMITTED) {
            return $payload;
        }

        $participant->loadMissing(['answers', 'examSession.exam.examQuestions.questionBank']);
        $exam = $participant->examSession->exam;
        $byQuestion = $exam->examQuestions->keyBy('question_bank_id');
        $answers = $participant->answers->keyBy(fn ($a) => (int) $a->question_bank_id);
        $order = array_map('intval', $participant->question_order ?? []);

        foreach ($order as $index => $qid) {
            $eq = $byQuestion->get($qid);
            $snapshot = $eq?->scoringPayload($eq->questionBank);
            if (! $snapshot) {
                continue;
            }
            $answer = $answers->get($qid);
            $payload['questions'][] = $this->reviewQuestion(
                $index,
                $snapshot,
                $answer,
                (bool) $exam->shuffle_options,
                $participant->id.':'.$qid
            );
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function reviewQuestion(int $index, array $snapshot, ?ExamAnswer $answer, bool $shuffleOptions, string $shuffleSeed): array
    {
        $snapshot = ExamQuestionSnapshot::forStudentDisplay($snapshot, $shuffleOptions, $shuffleSeed);
        $type = (string) ($snapshot['type'] ?? '');
        $weight = (float) ($snapshot['weight'] ?? 0);
        $score = $answer && $answer->score !== null ? (float) $answer->score : null;
        $options = [];
        $selectedIds = [];
        if ($answer) {
            if ($answer->question_option_id) {
                $selectedIds[] = (int) $answer->question_option_id;
            }
            foreach ($answer->selected_option_ids ?? [] as $id) {
                $selectedIds[] = (int) $id;
            }
        }
        $selectedIds = array_values(array_unique($selectedIds));

        if (in_array($type, [QuestionBank::TYPE_PG, QuestionBank::TYPE_PG_KOMPLEKS], true)) {
            foreach ($snapshot['options'] ?? [] as $opt) {
                $id = (int) ($opt['id'] ?? 0);
                $options[] = [
                    'option_key' => $opt['option_key'] ?? '',
                    'body' => $opt['body'] ?? '',
                    'selected' => in_array($id, $selectedIds, true),
                    'correct' => ! empty($opt['is_correct']),
                ];
            }
        }

        return [
            'index' => $index,
            'type' => $type,
            'body' => $snapshot['body'] ?? '',
            'weight' => $weight,
            'score' => $score,
            'student_answer' => $this->studentAnswerLabel($type, $snapshot, $answer),
            'correct_answer' => $type === QuestionBank::TYPE_URAIAN ? null : $this->correctAnswerLabel($type, $snapshot),
            'options' => $options,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function studentAnswerLabel(string $type, array $snapshot, ?ExamAnswer $answer): string
    {
        if (! $answer || ! self::answerIsFilled($answer)) {
            return '(kosong)';
        }
        if ($type === QuestionBank::TYPE_PG || $type === QuestionBank::TYPE_PG_KOMPLEKS) {
            $selected = [];
            if ($answer->question_option_id) {
                $selected[] = (int) $answer->question_option_id;
            }
            foreach ($answer->selected_option_ids ?? [] as $id) {
                $selected[] = (int) $id;
            }
            $labels = [];
            foreach ($snapshot['options'] ?? [] as $opt) {
                if (in_array((int) ($opt['id'] ?? 0), $selected, true)) {
                    $labels[] = trim(($opt['option_key'] ?? '').'. '.self::plainText((string) ($opt['body'] ?? '')));
                }
            }

            return $labels ? implode('; ', $labels) : '(kosong)';
        }
        if ($type === QuestionBank::TYPE_MATCHING) {
            $rightById = [];
            foreach ($snapshot['matching_data']['right'] ?? [] as $right) {
                $rightById[(string) ($right['id'] ?? '')] = (string) ($right['text'] ?? '');
            }
            $leftById = [];
            foreach ($snapshot['matching_data']['left'] ?? [] as $left) {
                $leftById[(string) ($left['id'] ?? '')] = (string) ($left['text'] ?? '');
            }
            $parts = [];
            foreach ($answer->matching_answer ?? [] as $pair) {
                $l = (string) ($pair['left_id'] ?? '');
                $r = (string) ($pair['right_id'] ?? '');
                if ($l === '' || $r === '') {
                    continue;
                }
                $parts[] = ($leftById[$l] ?? $l).' → '.($rightById[$r] ?? $r);
            }

            return $parts ? implode('; ', $parts) : '(kosong)';
        }

        return trim((string) ($answer->answer_text ?? '')) ?: '(kosong)';
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function correctAnswerLabel(string $type, array $snapshot): string
    {
        if ($type === QuestionBank::TYPE_PG || $type === QuestionBank::TYPE_PG_KOMPLEKS) {
            $labels = [];
            foreach ($snapshot['options'] ?? [] as $opt) {
                if (! empty($opt['is_correct'])) {
                    $labels[] = trim(($opt['option_key'] ?? '').'. '.self::plainText((string) ($opt['body'] ?? '')));
                }
            }

            return $labels ? implode('; ', $labels) : '—';
        }
        if ($type === QuestionBank::TYPE_ISIAN) {
            $keys = ExamQuestionSnapshot::acceptedIsianAnswers($snapshot);

            return $keys ? implode(' / ', $keys) : '—';
        }
        if ($type === QuestionBank::TYPE_MATCHING) {
            $rightById = [];
            foreach ($snapshot['matching_data']['right'] ?? [] as $right) {
                $rightById[(string) ($right['id'] ?? '')] = (string) ($right['text'] ?? '');
            }
            $leftById = [];
            foreach ($snapshot['matching_data']['left'] ?? [] as $left) {
                $leftById[(string) ($left['id'] ?? '')] = (string) ($left['text'] ?? '');
            }
            $parts = [];
            foreach ($snapshot['matching_data']['correct'] ?? [] as $pair) {
                $l = (string) ($pair['left_id'] ?? $pair['l'] ?? '');
                $r = (string) ($pair['right_id'] ?? $pair['r'] ?? '');
                $parts[] = ($leftById[$l] ?? $l).' → '.($rightById[$r] ?? $r);
            }

            return $parts ? implode('; ', $parts) : '—';
        }

        return '—';
    }

    /**
     * @return array<string, mixed>
     */
    public function itemAnalysis(ExamSession $session): array
    {
        $session->loadMissing(['exam.examQuestions.questionBank', 'participants.answers', 'participants.student.class']);
        $submitted = $session->participants->where('status', ExamParticipant::STATUS_SUBMITTED)->values();
        $items = [];
        $number = 0;
        foreach ($session->exam->examQuestions as $eq) {
            $number++;
            $snapshot = $eq->scoringPayload($eq->questionBank);
            if (! $snapshot) {
                continue;
            }
            $qid = (int) $eq->question_bank_id;
            $weight = (float) ($snapshot['weight'] ?? 0);
            $type = (string) ($snapshot['type'] ?? '');
            $full = 0;
            $partial = 0;
            $zero = 0;
            $unanswered = 0;
            $unscored = 0;
            $scoreSum = 0.0;
            $scoredCount = 0;
            foreach ($submitted as $participant) {
                $answer = $participant->answers->firstWhere('question_bank_id', $qid);
                $filled = self::answerIsFilled($answer);
                if (! $filled) {
                    $unanswered++;
                    continue;
                }
                if (! $answer || $answer->score === null) {
                    $unscored++;
                    continue;
                }
                $score = (float) $answer->score;
                $scoreSum += $score;
                $scoredCount++;
                if ($weight > 0 && $score + 0.001 >= $weight) {
                    $full++;
                } elseif ($score > 0) {
                    $partial++;
                } else {
                    $zero++;
                }
            }
            $items[] = [
                'number' => $number,
                'question_bank_id' => $qid,
                'type' => $type,
                'body' => self::plainText((string) ($snapshot['body'] ?? '')),
                'weight' => $weight,
                'submitted' => $submitted->count(),
                'full' => $full,
                'partial' => $partial,
                'zero' => $zero,
                'unanswered' => $unanswered,
                'unscored' => $unscored,
                'percent_full' => $submitted->count() > 0 ? round($full / $submitted->count() * 100, 1) : null,
                'avg_score' => $scoredCount > 0 ? round($scoreSum / $scoredCount, 2) : null,
            ];
        }

        return [
            'submitted' => $submitted->count(),
            'note' => 'Nomor mengikuti urutan paket ujian. Layar siswa bisa berbeda karena soal diacak. Persen benar dihitung dari seluruh peserta yang mengumpulkan; jawaban kosong dihitung tidak benar.',
            'items' => $items,
        ];
    }

    /**
     * Papan koreksi uraian: satu soal, semua peserta yang sudah mengumpulkan.
     *
     * @return array<string, mixed>
     */
    public function essayBoard(ExamSession $session): array
    {
        $session->loadMissing(['exam.examQuestions.questionBank', 'participants.student.class', 'participants.answers']);
        $submitted = $session->participants
            ->where('status', ExamParticipant::STATUS_SUBMITTED)
            ->sortBy(fn ($p) => sprintf('%s-%05d', $p->student?->class?->name ?? '', (int) ($p->participant_order ?? 99999)))
            ->values();
        $questions = [];
        $number = 0;
        foreach ($session->exam->examQuestions as $eq) {
            $number++;
            $snapshot = $eq->scoringPayload($eq->questionBank);
            if (! $snapshot || ($snapshot['type'] ?? '') !== QuestionBank::TYPE_URAIAN) {
                continue;
            }
            $qid = (int) $eq->question_bank_id;
            $weight = (float) ($snapshot['weight'] ?? 0);
            $rows = [];
            $pending = 0;
            foreach ($submitted as $participant) {
                $answer = $participant->answers->firstWhere('question_bank_id', $qid);
                $score = $answer && $answer->score !== null ? (float) $answer->score : null;
                if ($score === null) {
                    $pending++;
                }
                $rows[] = [
                    'participant_id' => $participant->id,
                    'student_name' => $participant->student?->name,
                    'class_name' => $participant->student?->class?->name,
                    'answer_id' => $answer?->id,
                    'answer_text' => $answer?->answer_text,
                    'score' => $score,
                    'weight' => $weight,
                ];
            }
            $questions[] = [
                'number' => $number,
                'question_bank_id' => $qid,
                'body' => $snapshot['body'] ?? '',
                'weight' => $weight,
                'pending' => $pending,
                'answers' => $rows,
            ];
        }

        return ['questions' => $questions];
    }

    public function scoreEssay(ExamParticipant $participant, int $questionBankId, float $score): ExamAnswer
    {
        $participant->loadMissing('examSession.exam.examQuestions.questionBank');
        $eq = $participant->examSession->exam->examQuestions->firstWhere('question_bank_id', $questionBankId);
        $snapshot = $eq?->scoringPayload($eq->questionBank);
        if (! $snapshot || ($snapshot['type'] ?? '') !== QuestionBank::TYPE_URAIAN) {
            throw new \InvalidArgumentException('Soal ini bukan uraian pada paket ujian.');
        }
        $order = array_map('intval', $participant->question_order ?? []);
        if ($participant->status === ExamParticipant::STATUS_STARTED && $order && ! in_array($questionBankId, $order, true)) {
            throw new \InvalidArgumentException('Soal tidak termasuk ujian peserta ini.');
        }
        $weight = (float) ($snapshot['weight'] ?? 0);
        $score = max(0, min($score, $weight > 0 ? $weight : $score));
        $answer = ExamAnswer::updateOrCreate(
            [
                'exam_participant_id' => $participant->id,
                'question_bank_id' => $questionBankId,
            ],
            [
                'score' => $score,
                'saved_at' => now(),
            ]
        );
        $this->recomputeParticipantScore($participant);

        return $answer->fresh();
    }

    /**
     * Salin nilai sesi (skala 0–100) ke buku nilai. Menimpa kolom yang sama pada semester aktif.
     *
     * @return array{copied:int,skipped:int,below_kkm:int,grade_type:string,semester_id:int}
     */
    public function copyScoresToGradeBook(
        ExamSession $session,
        string $gradeType,
        int $semesterId,
        int $institutionId,
        ?int $employeeId
    ): array {
        if ($gradeType === Grade::TYPE_NILAI_AKHIR || ! Grade::isValidType($gradeType)) {
            throw new \InvalidArgumentException('Pilih UTS, UAS, atau Penilaian. Nilai akhir dihitung di buku nilai.');
        }
        $session->loadMissing(['exam', 'participants.student.class']);
        $exam = $session->exam;
        if (! $exam->subject_id) {
            throw new \InvalidArgumentException('Ujian belum punya mata pelajaran.');
        }
        $academicYearId = \App\Models\Semester::query()->where('id', $semesterId)->value('academic_year_id');
        $copied = 0;
        $skipped = 0;
        $below = 0;
        $note = 'Dari ujian online: '.$exam->name.' / '.$session->name;

        foreach ($session->participants as $participant) {
            if ($participant->status !== ExamParticipant::STATUS_SUBMITTED
                || $participant->score === null
                || (float) $participant->score_max <= 0
                || ! $participant->student?->class_id) {
                $skipped++;
                continue;
            }
            $value = round(((float) $participant->score / (float) $participant->score_max) * 100, 2);
            $value = max(0, min(100, $value));
            $classId = (int) $participant->student->class_id;
            Grade::updateOrCreate(
                [
                    'student_id' => $participant->student_id,
                    'subject_id' => $exam->subject_id,
                    'semester_id' => $semesterId,
                    'grade_type' => $gradeType,
                ],
                [
                    'institution_id' => $institutionId,
                    'academic_year_id' => $academicYearId,
                    'class_id' => $classId,
                    'employee_id' => $employeeId,
                    'value' => $value,
                    'notes' => $note,
                ]
            );
            $copied++;
            $gradeLevel = $participant->student->class?->grade;
            if ($gradeLevel !== null) {
                $kkm = SubjectKkm::query()
                    ->where('institution_id', $institutionId)
                    ->where('subject_id', $exam->subject_id)
                    ->where('semester_id', $semesterId)
                    ->where('grade', (int) $gradeLevel)
                    ->value('kkm');
                if (SubjectKkm::isTuntas($value, $kkm) === false) {
                    $below++;
                }
            }
        }

        return [
            'copied' => $copied,
            'skipped' => $skipped,
            'below_kkm' => $below,
            'grade_type' => $gradeType,
            'semester_id' => $semesterId,
        ];
    }

    public static function plainText(string $html): string
    {
        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        if (mb_strlen($text) > 180) {
            return mb_substr($text, 0, 177).'…';
        }

        return $text;
    }
}

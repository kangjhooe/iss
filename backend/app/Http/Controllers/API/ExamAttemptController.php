<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveExamAnswerRequest;
use App\Models\ExamAnswer;
use App\Models\ExamParticipant;
use App\Models\ExamSession;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExamAttemptController extends Controller
{
    public function __construct(
        protected ExamService $examService
    ) {}

    /**
     * Enter exam: either (login_token + entry_pin) or (entry_pin + nomor_urut).
     * Siswa cukup input kode ujian 6 huruf dari pengawas + no. urut di kartu.
     * Returns exam info and login_token (for subsequent getQuestion/saveAnswer/submit).
     */
    public function enter(Request $request): JsonResponse
    {
        $entryPin = strtoupper(trim((string) $request->input('entry_pin', '')));
        if (strlen($entryPin) !== 6) {
            return response()->json(['message' => 'Kode ujian harus 6 huruf.'], 422);
        }

        $loginToken = $request->input('login_token');
        $nomorUrut = $request->input('nomor_urut');

        if ($loginToken !== null && $loginToken !== '') {
            return $this->enterWithLoginToken($loginToken, $entryPin);
        }

        if ($nomorUrut !== null && $nomorUrut !== '') {
            return $this->enterWithNomorUrut((int) $nomorUrut, $entryPin);
        }

        return response()->json([
            'message' => 'Isi kode ujian 6 huruf dari pengawas dan no. urut peserta (angka di kartu).',
        ], 422);
    }

    private function enterWithLoginToken(string $token, string $entryPin): JsonResponse
    {
        $participant = ExamParticipant::where('login_token', $token)->with(['examSession.exam.subject', 'student'])->first();
        if (!$participant) {
            return response()->json(['message' => 'Token tidak valid.'], 404);
        }
        return $this->finishEnter($participant, $entryPin);
    }

    private function enterWithNomorUrut(int $nomorUrut, string $entryPin): JsonResponse
    {
        if ($nomorUrut < 1) {
            return response()->json(['message' => 'No. urut tidak valid.'], 422);
        }
        $session = ExamSession::where('status', 'started')
            ->where('entry_pin', $entryPin)
            ->with(['exam.subject'])
            ->first();
        if (!$session) {
            return response()->json([
                'message' => 'Kode ujian salah atau sudah berubah. Minta kode terbaru ke pengawas (diperbarui tiap 20 menit).',
            ], 403);
        }
        $participants = $session->participants()->with(['examSession.exam.subject', 'student'])
            ->orderByRaw('COALESCE(participant_order, 999999) ASC')
            ->orderBy('id')
            ->get();
        $participant = $participants->get($nomorUrut - 1);
        if (!$participant) {
            return response()->json(['message' => 'No. urut tidak ditemukan untuk sesi ini.'], 404);
        }
        return $this->finishEnter($participant, $entryPin);
    }

    private function finishEnter(ExamParticipant $participant, string $entryPin): JsonResponse
    {
        $session = $participant->examSession;
        $exam = $session->exam;

        $sessionPin = $session->entry_pin ? strtoupper(trim($session->entry_pin)) : '';
        if ($session->status === ExamSession::STATUS_STARTED && ($sessionPin === '' || $entryPin !== $sessionPin)) {
            return response()->json([
                'message' => 'Kode ujian salah atau sudah berubah. Minta kode terbaru ke pengawas (diperbarui tiap 20 menit).',
            ], 403);
        }

        if ($participant->status === ExamParticipant::STATUS_STARTED
            && ($session->status === ExamSession::STATUS_ENDED || $this->examService->isPastDeadline($participant))) {
            $this->examService->submitParticipant($participant);
            $participant = $participant->fresh();
        }

        if ($participant->status === ExamParticipant::STATUS_SUBMITTED) {
            return response()->json([
                'message' => 'Anda sudah mengirimkan jawaban.',
                'code' => 'already_submitted',
                'login_token' => $participant->login_token,
                'participant' => [
                    'status' => $participant->status,
                    'score' => $participant->score_released ? $participant->score : null,
                    'score_max' => $participant->score_released ? $participant->score_max : null,
                    'score_released' => (bool) $participant->score_released,
                    'can_retry' => $this->examService->canRetry($participant),
                    'attempt_number' => (int) ($participant->attempt_number ?? 1),
                    'max_attempts' => $exam->max_attempts !== null ? (int) $exam->max_attempts : null,
                ],
            ], 200);
        }

        if ($session->status !== ExamSession::STATUS_STARTED) {
            return response()->json([
                'message' => 'Sesi ujian belum diaktifkan. Silakan tunggu hingga admin mengaktifkan.',
                'session_status' => $session->status,
                'code' => 'session_inactive',
            ], 403);
        }

        if ($participant->status === ExamParticipant::STATUS_REGISTERED) {
            $this->examService->startParticipant($participant);
            $participant = $participant->fresh();
        }

        $total = count($participant->question_order ?? []);
        $durationMinutes = $exam->duration_minutes;
        $startedAt = $participant->started_at->toIso8601String();

        return response()->json([
            'participant_id' => $participant->id,
            'login_token' => $participant->login_token,
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'subject' => $exam->subject ? ['id' => $exam->subject->id, 'name' => $exam->subject->name] : null,
                'duration_minutes' => $durationMinutes,
            ],
            'session' => [
                'id' => $session->id,
                'name' => $session->name,
            ],
            'total_questions' => $total,
            'started_at' => $startedAt,
            'seconds_remaining' => $this->examService->secondsRemaining($participant),
            'status' => $participant->status,
        ]);
    }

    /**
     * Get question at 0-based index.
     */
    public function question(Request $request): JsonResponse
    {
        $request->validate(['login_token' => 'required|string', 'index' => 'required|integer|min:0']);
        $participant = $this->getOpenParticipant($request->input('login_token'));
        if ($participant instanceof JsonResponse) {
            return $participant;
        }
        $index = (int) $request->input('index');
        $data = $this->examService->getQuestionAt($participant, $index);
        if (!$data) {
            return response()->json(['message' => 'Soal tidak ditemukan.'], 404);
        }
        $this->examService->touchPresence($participant);
        $data['seconds_remaining'] = $this->examService->secondsRemaining($participant);
        return response()->json($data);
    }

    /**
     * Save answer for a question.
     */
    public function saveAnswer(SaveExamAnswerRequest $request): JsonResponse
    {
        $participant = $this->getOpenParticipant($request->input('login_token'));
        if ($participant instanceof JsonResponse) {
            return $participant;
        }

        $qbId = $request->input('question_bank_id');
        $order = $participant->question_order ?? [];
        if (!in_array((int) $qbId, array_map('intval', $order))) {
            return response()->json(['message' => 'Soal tidak termasuk dalam ujian ini.'], 422);
        }

        $answer = ExamAnswer::updateOrCreate(
            [
                'exam_participant_id' => $participant->id,
                'question_bank_id' => $qbId,
            ],
            [
                'answer_text' => $request->input('answer_text'),
                'question_option_id' => $request->input('question_option_id'),
                'selected_option_ids' => $request->input('selected_option_ids'),
                'matching_answer' => $request->input('matching_answer'),
                'saved_at' => now(),
            ]
        );

        $this->examService->touchPresence($participant);

        return response()->json([
            'message' => 'Jawaban disimpan.',
            'saved_at' => $answer->saved_at->toIso8601String(),
            'seconds_remaining' => $this->examService->secondsRemaining($participant),
            'answered' => ExamService::answerIsFilled($answer),
        ]);
    }

    /**
     * Submit exam (final).
     */
    public function submit(Request $request): JsonResponse
    {
        $request->validate(['login_token' => 'required|string']);
        $participant = $this->getOpenParticipant($request->input('login_token'));
        if ($participant instanceof JsonResponse) {
            return $participant;
        }

        $participant = $this->examService->submitParticipant($participant);

        return response()->json([
            'message' => 'Jawaban berhasil dikirim.',
            'submitted_at' => $participant->submitted_at?->toIso8601String(),
            'code' => 'submitted',
            'score' => $participant->score_released && $participant->score !== null ? (float) $participant->score : null,
            'score_max' => $participant->score_released && $participant->score_max !== null ? (float) $participant->score_max : null,
            'score_released' => (bool) $participant->score_released,
            'can_retry' => $this->examService->canRetry($participant),
            'attempt_number' => (int) ($participant->attempt_number ?? 1),
            'max_attempts' => $participant->examSession->exam->max_attempts !== null
                ? (int) $participant->examSession->exam->max_attempts
                : null,
        ]);
    }

    /**
     * Peta soal + sisa waktu. Token yang tersimpan cukup untuk melanjutkan setelah refresh.
     */
    public function progress(Request $request): JsonResponse
    {
        $request->validate(['login_token' => 'required|string']);
        $participant = ExamParticipant::where('login_token', $request->input('login_token'))
            ->with(['examSession.exam.subject', 'answers'])
            ->first();
        if (! $participant) {
            return response()->json(['message' => 'Token tidak valid.'], 404);
        }
        if ($participant->status === ExamParticipant::STATUS_STARTED) {
            $this->examService->touchPresence($participant);
        }
        if ($participant->status === ExamParticipant::STATUS_STARTED
            && ($participant->examSession->status === ExamSession::STATUS_ENDED || $this->examService->isPastDeadline($participant))) {
            $this->examService->submitParticipant($participant);
            $participant = $participant->fresh(['examSession.exam.subject', 'answers']);
        }
        if ($participant->status !== ExamParticipant::STATUS_SUBMITTED
            && $participant->examSession->status !== ExamSession::STATUS_STARTED) {
            return response()->json([
                'message' => 'Sesi ujian tidak aktif.',
                'code' => 'session_inactive',
            ], 403);
        }

        return response()->json($this->examService->attemptState($participant));
    }

    public function flag(Request $request): JsonResponse
    {
        $request->validate([
            'login_token' => 'required|string',
            'question_bank_id' => 'required|integer',
            'flagged' => 'required|boolean',
        ]);
        $participant = $this->getOpenParticipant($request->input('login_token'));
        if ($participant instanceof JsonResponse) {
            return $participant;
        }
        $this->examService->setQuestionFlag(
            $participant,
            (int) $request->input('question_bank_id'),
            $request->boolean('flagged')
        );

        return response()->json(['message' => 'Tanda soal disimpan.']);
    }

    /**
     * Pembahasan jawaban. Kunci hanya jika nilai sudah dirilis.
     */
    public function review(Request $request): JsonResponse
    {
        $request->validate(['login_token' => 'required|string']);
        $participant = ExamParticipant::where('login_token', $request->input('login_token'))
            ->with(['examSession.exam.examQuestions.questionBank', 'answers'])
            ->first();
        if (! $participant) {
            return response()->json(['message' => 'Token tidak valid.'], 404);
        }
        if ($participant->status !== ExamParticipant::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Ujian belum dikumpulkan.', 'code' => 'not_submitted'], 403);
        }

        return response()->json($this->examService->reviewForParticipant($participant));
    }

    /**
     * Ulangi latihan selama sesi masih berjalan dan kuota percobaan tersisa.
     */
    public function retry(Request $request): JsonResponse
    {
        $request->validate(['login_token' => 'required|string']);
        $participant = ExamParticipant::where('login_token', $request->input('login_token'))
            ->with(['examSession.exam.subject'])
            ->first();
        if (! $participant) {
            return response()->json(['message' => 'Token tidak valid.'], 404);
        }
        try {
            $participant = $this->examService->retryPractice($participant);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $participant->loadMissing('examSession.exam.subject');
        $exam = $participant->examSession->exam;

        return response()->json([
            'participant_id' => $participant->id,
            'login_token' => $participant->login_token,
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'subject' => $exam->subject ? ['id' => $exam->subject->id, 'name' => $exam->subject->name] : null,
                'duration_minutes' => $exam->duration_minutes,
            ],
            'session' => [
                'id' => $participant->exam_session_id,
                'name' => $participant->examSession->name,
            ],
            'total_questions' => count($participant->question_order ?? []),
            'started_at' => $participant->started_at?->toIso8601String(),
            'seconds_remaining' => $this->examService->secondsRemaining($participant),
            'status' => $participant->status,
            'attempt_number' => (int) $participant->attempt_number,
            'max_attempts' => $exam->max_attempts !== null ? (int) $exam->max_attempts : null,
        ]);
    }

    private function getOpenParticipant(string $token): ExamParticipant|JsonResponse
    {
        $participant = ExamParticipant::where('login_token', $token)->with('examSession.exam')->first();
        if (! $participant) {
            return response()->json(['message' => 'Token tidak valid.'], 404);
        }
        if ($participant->status === ExamParticipant::STATUS_SUBMITTED) {
            return response()->json([
                'message' => 'Anda sudah mengirimkan jawaban.',
                'code' => 'already_submitted',
            ], 403);
        }
        $sessionEnded = $participant->examSession->status === ExamSession::STATUS_ENDED;
        if ($participant->status === ExamParticipant::STATUS_STARTED
            && ($sessionEnded || $this->examService->isPastDeadline($participant))) {
            $this->examService->submitParticipant($participant);

            return response()->json([
                'message' => $sessionEnded
                    ? 'Sesi ujian sudah diakhiri. Jawaban Anda dikumpulkan.'
                    : 'Waktu ujian habis. Jawaban dikumpulkan.',
                'code' => $sessionEnded ? 'session_ended' : 'time_up',
            ], 403);
        }
        if ($participant->examSession->status !== ExamSession::STATUS_STARTED) {
            return response()->json(['message' => 'Sesi ujian tidak aktif.', 'code' => 'session_inactive'], 403);
        }
        if ($participant->status !== ExamParticipant::STATUS_STARTED) {
            return response()->json(['message' => 'Ujian belum dimulai.', 'code' => 'not_started'], 403);
        }

        return $participant;
    }
}

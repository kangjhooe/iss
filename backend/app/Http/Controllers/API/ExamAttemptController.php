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

        if ($session->status !== 'started') {
            return response()->json([
                'message' => 'Sesi ujian belum diaktifkan. Silakan tunggu hingga admin mengaktifkan.',
                'session_status' => $session->status,
            ], 403);
        }

        $sessionPin = $session->entry_pin ? strtoupper(trim($session->entry_pin)) : '';
        if ($sessionPin === '' || $entryPin !== $sessionPin) {
            return response()->json([
                'message' => 'Kode ujian salah atau sudah berubah. Minta kode terbaru ke pengawas (diperbarui tiap 20 menit).',
            ], 403);
        }

        if ($participant->status === ExamParticipant::STATUS_SUBMITTED) {
            return response()->json([
                'message' => 'Anda sudah mengirimkan jawaban.',
                'participant' => [
                    'status' => $participant->status,
                    'score' => $participant->score_released ? $participant->score : null,
                    'score_max' => $participant->score_released ? $participant->score_max : null,
                ],
            ], 200);
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
            'status' => $participant->status,
        ]);
    }

    /**
     * Get question at 0-based index.
     */
    public function question(Request $request): JsonResponse
    {
        $request->validate(['login_token' => 'required|string', 'index' => 'required|integer|min:0']);
        $participant = $this->getParticipantByToken($request->input('login_token'));
        if ($participant instanceof JsonResponse) {
            return $participant;
        }
        $index = (int) $request->input('index');
        $data = $this->examService->getQuestionAt($participant, $index);
        if (!$data) {
            return response()->json(['message' => 'Soal tidak ditemukan.'], 404);
        }
        return response()->json($data);
    }

    /**
     * Save answer for a question.
     */
    public function saveAnswer(SaveExamAnswerRequest $request): JsonResponse
    {
        $participant = $this->getParticipantByToken($request->input('login_token'));
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

        return response()->json(['message' => 'Jawaban disimpan.', 'saved_at' => $answer->saved_at->toIso8601String()]);
    }

    /**
     * Submit exam (final).
     */
    public function submit(Request $request): JsonResponse
    {
        $request->validate(['login_token' => 'required|string']);
        $participant = $this->getParticipantByToken($request->input('login_token'));
        if ($participant instanceof JsonResponse) {
            return $participant;
        }

        if ($participant->status === ExamParticipant::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Anda sudah mengirimkan jawaban.'], 422);
        }

        $participant->update([
            'status' => ExamParticipant::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $this->examService->computeAutoScoreForParticipant($participant);

        return response()->json([
            'message' => 'Jawaban berhasil dikirim.',
            'submitted_at' => $participant->submitted_at->toIso8601String(),
        ]);
    }

    private function getParticipantByToken(string $token): ExamParticipant|JsonResponse
    {
        $participant = ExamParticipant::where('login_token', $token)->with('examSession')->first();
        if (!$participant) {
            return response()->json(['message' => 'Token tidak valid.'], 404);
        }
        if ($participant->examSession->status !== 'started') {
            return response()->json(['message' => 'Sesi ujian tidak aktif.'], 403);
        }
        if ($participant->status === ExamParticipant::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Anda sudah mengirimkan jawaban.'], 403);
        }
        return $participant;
    }
}

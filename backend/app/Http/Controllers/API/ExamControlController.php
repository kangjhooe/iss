<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Models\ExamAnswer;
use App\Models\ExamParticipant;
use App\Models\ExamSession;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamControlController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected ExamService $examService
    ) {}

    /**
     * Start session (manual start).
     */
    public function startSession(Request $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $session = $this->examService->startSession($exam_session);
        return response()->json(['message' => 'Sesi ujian dimulai.', 'session' => $session]);
    }

    /**
     * Regenerate entry PIN for session (kode ujian 6 huruf; diperbarui tiap 20 menit).
     */
    public function regenerateEntryPin(Request $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        if ($exam_session->status !== ExamSession::STATUS_STARTED) {
            return response()->json(['message' => 'Sesi harus berstatus dimulai.'], 422);
        }
        $pin = $this->examService->regenerateEntryPin($exam_session);
        return response()->json([
            'message' => 'Kode ujian diperbarui.',
            'entry_pin' => $pin,
            'entry_pin_updated_at' => $exam_session->fresh()->entry_pin_updated_at?->toIso8601String(),
        ]);
    }

    /**
     * End session.
     */
    public function endSession(Request $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $session = $this->examService->endSession($exam_session, true);
        return response()->json(['message' => 'Sesi ujian diakhiri.', 'session' => $session]);
    }

    /**
     * Reset session to draft (optional: reset participants).
     */
    public function resetSession(Request $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $resetParticipants = $request->boolean('reset_participants', true);
        $session = $this->examService->resetSession($exam_session, $resetParticipants);
        return response()->json(['message' => 'Sesi ujian direset.', 'session' => $session]);
    }

    /**
     * Compute auto scores for all submitted in session.
     */
    public function computeScores(Request $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $this->examService->computeAutoScoresForSession($exam_session);
        return response()->json(['message' => 'Nilai otomatis dihitung.']);
    }

    /**
     * Release score for a participant (visible to student).
     */
    public function releaseScore(Request $request, ExamParticipant $exam_participant): JsonResponse
    {
        if ($exam_participant->examSession->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Peserta tidak ditemukan.'], 404);
        }
        $this->examService->releaseScore($exam_participant);
        return response()->json(['message' => 'Nilai dirilis ke siswa.']);
    }

    /**
     * Update score for one answer (uraian manual). Then recompute participant total.
     */
    public function updateAnswerScore(Request $request, ExamAnswer $exam_answer): JsonResponse
    {
        $participant = $exam_answer->examParticipant;
        if (!$participant || $participant->examSession->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Jawaban tidak ditemukan.'], 404);
        }
        $request->validate(['score' => 'required|numeric|min:0']);
        $q = $exam_answer->questionBank;
        $maxScore = $q ? (float) $q->weight : 100;
        $score = min((float) $request->input('score'), $maxScore);
        $exam_answer->update(['score' => $score]);
        $this->examService->recomputeParticipantScore($participant);
        return response()->json(['message' => 'Nilai disimpan.', 'score' => $score]);
    }

    /**
     * Recompute total score for one participant (setelah koreksi uraian).
     */
    public function recomputeParticipant(Request $request, ExamParticipant $exam_participant): JsonResponse
    {
        if ($exam_participant->examSession->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Peserta tidak ditemukan.'], 404);
        }
        $this->examService->recomputeParticipantScore($exam_participant);
        $exam_participant->refresh();
        return response()->json([
            'message' => 'Nilai dihitung ulang.',
            'score' => $exam_participant->score,
            'score_max' => $exam_participant->score_max,
        ]);
    }

    /**
     * Reset satu peserta: hapus jawaban, kembalikan status ke Terdaftar. Peserta bisa masuk lagi dengan token + kode ujian.
     */
    public function resetParticipant(Request $request, ExamParticipant $exam_participant): JsonResponse
    {
        if ($exam_participant->examSession->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Peserta tidak ditemukan.'], 404);
        }
        $this->examService->resetParticipant($exam_participant);
        return response()->json(['message' => 'Peserta direset. Mereka bisa masuk ujian lagi dengan token dan kode ujian.']);
    }
}

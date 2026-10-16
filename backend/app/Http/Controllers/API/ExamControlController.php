<?php

namespace App\Http\Controllers\API;

use App\Exports\ExamSessionResultsExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Resources\ExamParticipantResource;
use App\Http\Resources\ExamSessionResource;
use App\Models\ExamAnswer;
use App\Models\ExamParticipant;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Services\ExamService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Maatwebsite\Excel\Excel as ExcelManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExamControlController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected ExamService $examService
    ) {}

    /**
     * Live monitoring snapshot: session + participants + status counts.
     */
    public function monitor(Request $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }

        $exam_session->load(['exam.subject', 'exam.institution']);
        $participants = $exam_session->participants()
            ->with(['student', 'examSession.exam.institution'])
            ->orderByRaw('COALESCE(participant_order, 999999) ASC')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => [
                'session' => (new ExamSessionResource($exam_session))->resolve(),
                'summary' => $this->examService->monitorSummary($exam_session),
                'participants' => ExamParticipantResource::collection($participants)->resolve(),
            ],
        ]);
    }

    /**
     * Export hasil sesi (Excel).
     */
    public function exportResults(Request $request, ExamSession $exam_session): BinaryFileResponse|JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }

        $exam_session->load(['exam.institution']);
        $participants = $exam_session->participants()
            ->with(['student', 'examSession.exam.institution'])
            ->orderByRaw('COALESCE(participant_order, 999999) ASC')
            ->orderBy('id')
            ->get();

        $institution = $exam_session->exam->institution;
        foreach ($participants as $p) {
            $nomor = null;
            if ($institution && $p->participant_order !== null) {
                $date = $exam_session->scheduled_start_at ?? $exam_session->started_at ?? now();
                $nomor = $institution->buildNomorPeserta((int) $p->participant_order, $date);
            }
            $p->nomor_peserta_export = $nomor;
        }

        $safeName = preg_replace('/[^\w\-]+/u', '_', $exam_session->name) ?: 'sesi';
        $filename = 'hasil-ujian-'.$safeName.'-'.now()->format('Ymd-His').'.xlsx';

        $export = new ExamSessionResultsExport($participants, $exam_session->name);

        return app(ExcelManager::class)->download($export, $filename, ExcelManager::XLSX);
    }

    /**
     * Berita acara pelaksanaan ujian (PDF).
     */
    public function beritaAcara(Request $request, ExamSession $exam_session)
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }

        $exam_session->load(['exam.subject', 'exam.institution']);
        $participants = $exam_session->participants()
            ->with(['student.class'])
            ->orderByRaw('COALESCE(participant_order, 999999) ASC')
            ->orderBy('id')
            ->get();

        $rows = [];
        $hadir = 0;
        $selesai = 0;
        foreach ($participants as $index => $participant) {
            $present = in_array($participant->status, [
                ExamParticipant::STATUS_STARTED,
                ExamParticipant::STATUS_SUBMITTED,
            ], true);
            if ($present) {
                $hadir++;
            }
            if ($participant->status === ExamParticipant::STATUS_SUBMITTED) {
                $selesai++;
            }
            $rows[] = [
                'no' => $index + 1,
                'name' => $participant->student?->name ?? '–',
                'nis' => $participant->student?->nis ?? '–',
                'class' => $participant->student?->class?->name ?? '–',
                'status' => $present ? 'Hadir' : 'Tidak hadir',
                'started_at' => $participant->started_at?->timezone('Asia/Jakarta')->format('H:i'),
                'submitted_at' => $participant->submitted_at?->timezone('Asia/Jakarta')->format('H:i'),
            ];
        }

        $pdf = DomPDF::loadView('exam.berita_acara', [
            'institution' => $exam_session->exam->institution,
            'exam' => $exam_session->exam,
            'session' => $exam_session,
            'rows' => $rows,
            'total' => count($rows),
            'hadir' => $hadir,
            'tidak_hadir' => count($rows) - $hadir,
            'selesai' => $selesai,
            'printed_at' => now()->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        $filename = 'berita-acara-'.preg_replace('/[^\w\-]+/u', '_', $exam_session->name).'.pdf';
        if ($request->boolean('preview')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

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
     * Release scores for all submitted participants in the session.
     */
    public function releaseAllScores(Request $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $count = $this->examService->releaseAllScores($exam_session);

        return response()->json([
            'message' => $count > 0
                ? "Nilai dirilis untuk {$count} peserta."
                : 'Tidak ada nilai baru yang perlu dirilis.',
            'released_count' => $count,
        ]);
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

    /**
     * Tambah waktu untuk satu peserta yang masih mengerjakan.
     */
    public function extraTime(Request $request, ExamParticipant $exam_participant): JsonResponse
    {
        if ($exam_participant->examSession->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Peserta tidak ditemukan.'], 404);
        }
        if ($exam_participant->status !== ExamParticipant::STATUS_STARTED) {
            return response()->json(['message' => 'Waktu hanya bisa ditambah saat peserta masih mengerjakan.'], 422);
        }
        $minutes = (int) $request->validate(['minutes' => 'required|integer|min:1|max:60'])['minutes'];
        $participant = $this->examService->extendTime($exam_participant, $minutes);

        return response()->json([
            'message' => "Waktu ditambah {$minutes} menit.",
            'extra_seconds' => (int) $participant->extra_seconds,
            'seconds_remaining' => $this->examService->secondsRemaining($participant),
        ]);
    }

    /**
     * Kumpulkan jawaban satu peserta dari meja pengawas.
     */
    public function forceSubmit(Request $request, ExamParticipant $exam_participant): JsonResponse
    {
        if ($exam_participant->examSession->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Peserta tidak ditemukan.'], 404);
        }
        if ($exam_participant->status !== ExamParticipant::STATUS_STARTED) {
            return response()->json(['message' => 'Peserta tidak sedang mengerjakan.'], 422);
        }
        $participant = $this->examService->submitParticipant($exam_participant);

        return response()->json([
            'message' => 'Jawaban peserta dikumpulkan.',
            'score' => $participant->score !== null ? (float) $participant->score : null,
            'score_max' => $participant->score_max !== null ? (float) $participant->score_max : null,
        ]);
    }

    public function itemAnalysis(Request $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }

        return response()->json(['data' => $this->examService->itemAnalysis($exam_session)]);
    }

    public function essayBoard(Request $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }

        return response()->json(['data' => $this->examService->essayBoard($exam_session)]);
    }

    public function scoreEssay(Request $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $data = $request->validate([
            'exam_participant_id' => 'required|integer',
            'question_bank_id' => 'required|integer',
            'score' => 'required|numeric|min:0',
        ]);
        $participant = ExamParticipant::query()
            ->where('exam_session_id', $exam_session->id)
            ->where('id', $data['exam_participant_id'])
            ->first();
        if (! $participant || $participant->status !== ExamParticipant::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Peserta belum mengumpulkan jawaban.'], 422);
        }
        try {
            $answer = $this->examService->scoreEssay($participant, (int) $data['question_bank_id'], (float) $data['score']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $participant->refresh();

        return response()->json([
            'message' => 'Nilai uraian disimpan.',
            'answer_id' => $answer->id,
            'score' => $answer->score !== null ? (float) $answer->score : null,
            'total' => $participant->score !== null ? (float) $participant->score : null,
            'score_max' => $participant->score_max !== null ? (float) $participant->score_max : null,
        ]);
    }

    /**
     * Salin nilai sesi (skala 100) ke kolom UTS, UAS, atau Penilaian di buku nilai.
     */
    public function copyToGrades(Request $request, ExamSession $exam_session): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if ($exam_session->exam->institution_id != $institutionId) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $data = $request->validate([
            'grade_type' => 'required|string|max:32',
        ]);
        $gradeType = (string) $data['grade_type'];
        if ($gradeType === Grade::TYPE_NILAI_AKHIR || ! Grade::isValidType($gradeType)) {
            return response()->json([
                'message' => 'Pilih UTS, UAS, atau Penilaian. Nilai akhir tetap dihitung di buku nilai.',
            ], 422);
        }
        $semesterId = (int) ($request->input('semester_id') ?: 0);
        if ($semesterId <= 0) {
            $semesterId = (int) (InstitutionContext::institutionActivePeriod((int) $institutionId, $request)['active_semester_id'] ?? 0);
        }
        if ($semesterId <= 0) {
            return response()->json(['message' => 'Semester aktif belum diatur di institusi.'], 422);
        }
        $user = $request->user();
        $user?->loadMissing(['teacherProfile', 'employeeProfile']);
        $employeeId = $user?->teacherProfile?->id ?? $user?->employeeProfile?->id;

        try {
            $result = $this->examService->copyScoresToGradeBook(
                $exam_session,
                $gradeType,
                $semesterId,
                (int) $institutionId,
                $employeeId ? (int) $employeeId : null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $label = Grade::getTypeLabel($gradeType);
        $message = "{$result['copied']} nilai disalin ke {$label}.";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} peserta dilewati (belum ada nilai atau belum punya kelas).";
        }
        if ($result['below_kkm'] > 0) {
            $message .= " {$result['below_kkm']} di bawah KKM.";
        }

        return response()->json([
            'message' => $message,
            'data' => $result,
        ]);
    }
}

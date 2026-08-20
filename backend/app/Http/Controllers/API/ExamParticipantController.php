<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\ExamParticipantBulkRequest;
use App\Http\Resources\ExamParticipantResource;
use App\Models\ExamParticipant;
use App\Models\ExamSession;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExamParticipantController extends Controller
{
    use ResolvesInstitution;

    /**
     * List participants of a session.
     */
    public function index(Request $request, ExamSession $exam_session): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $participants = $exam_session->participants()
            ->with(['student.class', 'examSession.exam.institution'])
            ->orderByRaw('COALESCE(participant_order, 999999) ASC')
            ->orderBy('id')
            ->get();
        return ExamParticipantResource::collection($participants);
    }

    /**
     * Add participants (students) to session. Generates login_token for each.
     */
    public function store(ExamParticipantBulkRequest $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $institutionId = $exam_session->exam->institution_id;
        $studentIds = $request->input('student_ids');
        $existingIds = $exam_session->participants()->pluck('student_id')->toArray();
        $toAdd = array_diff($studentIds, $existingIds);
        $students = Student::whereIn('id', $toAdd)->where('institution_id', $institutionId)->pluck('id');
        $added = 0;
        foreach ($students as $studentId) {
            ExamParticipant::create([
                'exam_session_id' => $exam_session->id,
                'student_id' => $studentId,
                'participant_order' => null,
                'login_token' => ExamParticipant::generateLoginToken(),
                'status' => ExamParticipant::STATUS_REGISTERED,
            ]);
            $added++;
        }
        return response()->json(['message' => "{$added} peserta ditambahkan.", 'added' => $added]);
    }

    /**
     * Generate participant_order for this session. Numbers continue across all sessions of the same exam
     * (e.g. session 1: 1–16, session 2: 17–...). Only assigns numbers when admin clicks Generate per session.
     */
    public function generateNumbers(Request $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $exam = $exam_session->exam;
        $sessionIds = $exam->sessions()->pluck('id');
        $maxOrder = (int) ExamParticipant::whereIn('exam_session_id', $sessionIds)->max('participant_order');

        $participants = $exam_session->participants()->orderByRaw('COALESCE(participant_order, 999999) ASC')->orderBy('id')->get();
        $order = $maxOrder + 1;
        foreach ($participants as $p) {
            $p->update(['participant_order' => $order]);
            $order++;
        }
        return response()->json(['message' => 'Nomor peserta di-generate.', 'count' => $participants->count()]);
    }

    /**
     * Reorder participants. Assigns participant_order continuing from max across other sessions of same exam.
     */
    public function reorder(Request $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $ids = $request->input('participant_ids');
        if (! is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'participant_ids harus array dan tidak boleh kosong.'], 422);
        }
        $ids = array_values(array_map('intval', $ids));
        $sessionParticipantIds = $exam_session->participants()->pluck('id')->toArray();
        foreach ($ids as $id) {
            if (! in_array($id, $sessionParticipantIds, true)) {
                return response()->json(['message' => 'Semua ID harus peserta dari sesi ini.'], 422);
            }
        }

        $exam = $exam_session->exam;
        $sessionIds = $exam->sessions()->pluck('id');
        $maxOrder = (int) ExamParticipant::whereIn('exam_session_id', $sessionIds)->where('exam_session_id', '!=', $exam_session->id)->max('participant_order');

        $order = $maxOrder + 1;
        foreach ($ids as $id) {
            ExamParticipant::where('id', $id)->where('exam_session_id', $exam_session->id)->update(['participant_order' => $order]);
            $order++;
        }
        return response()->json(['message' => 'Urutan peserta diperbarui.', 'count' => count($ids)]);
    }

    /**
     * Update participant (only participant_order when status is registered).
     */
    public function update(Request $request, ExamParticipant $exam_participant): JsonResponse
    {
        if ($exam_participant->examSession->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Peserta tidak ditemukan.'], 404);
        }
        if ($exam_participant->status !== ExamParticipant::STATUS_REGISTERED) {
            return response()->json(['message' => 'Hanya peserta dengan status Terdaftar yang dapat diedit.'], 422);
        }
        $order = $request->input('participant_order');
        if ($order === null || $order === '') {
            return response()->json(['message' => 'participant_order wajib diisi.'], 422);
        }
        $order = (int) $order;
        if ($order < 1) {
            return response()->json(['message' => 'Nomor peserta minimal 1.'], 422);
        }
        $exam = $exam_participant->examSession->exam;
        $sessionIds = $exam->sessions()->pluck('id');
        $exists = ExamParticipant::whereIn('exam_session_id', $sessionIds)
            ->where('participant_order', $order)
            ->where('id', '!=', $exam_participant->id)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Nomor peserta sudah dipakai peserta lain di ujian ini.'], 422);
        }
        $exam_participant->update(['participant_order' => $order]);
        return response()->json([
            'message' => 'Nomor peserta diperbarui.',
            'data' => new ExamParticipantResource($exam_participant->load(['student', 'examSession.exam.institution'])),
        ]);
    }

    /**
     * Remove a participant from session.
     */
    public function destroy(Request $request, ExamParticipant $exam_participant): JsonResponse
    {
        if ($exam_participant->examSession->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Peserta tidak ditemukan.'], 404);
        }
        if ($exam_participant->status === ExamParticipant::STATUS_STARTED || $exam_participant->status === ExamParticipant::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Peserta sudah mulai/sudah submit, tidak dapat dihapus.'], 422);
        }
        $exam_participant->delete();
        return response()->json(['message' => 'Peserta dihapus.']);
    }

    /**
     * Regenerate login token for participant.
     */
    public function regenerateToken(Request $request, ExamParticipant $exam_participant): JsonResponse
    {
        if ($exam_participant->examSession->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Peserta tidak ditemukan.'], 404);
        }
        $exam_participant->update(['login_token' => ExamParticipant::generateLoginToken()]);
        return response()->json([
            'message' => 'Token diperbarui.',
            'login_token' => $exam_participant->login_token,
        ]);
    }

    /**
     * Get participant answers for grading (soal uraian bisa diberi nilai manual).
     */
    public function answers(Request $request, ExamParticipant $exam_participant): JsonResponse
    {
        if ($exam_participant->examSession->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Peserta tidak ditemukan.'], 404);
        }
        $items = $exam_participant->answers()->with('questionBank')->orderBy('id')->get();
        $list = $items->map(function ($a) {
            $q = $a->questionBank;
            return [
                'id' => $a->id,
                'question_bank_id' => $a->question_bank_id,
                'type' => $q->type,
                'body' => $q->body,
                'weight' => (float) $q->weight,
                'answer_text' => $a->answer_text,
                'question_option_id' => $a->question_option_id,
                'score' => $a->score !== null ? (float) $a->score : null,
            ];
        });
        return response()->json(['data' => $list]);
    }

    /**
     * Print single participant card (PDF).
     */
    public function printCard(Request $request, ExamParticipant $exam_participant)
    {
        if ($exam_participant->examSession->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Peserta tidak ditemukan.'], 404);
        }
        $exam_participant->load(['student.class', 'examSession.exam.subject', 'examSession.exam.institution']);
        $exam = $exam_participant->examSession->exam;
        $session = $exam_participant->examSession;
        $institution = $exam->institution;

        $ordered = $session->participants()->orderByRaw('COALESCE(participant_order, 999999) ASC')->orderBy('id')->get();
        $nomorUrut = $ordered->search(fn ($p) => $p->id === $exam_participant->id) + 1;
        if ($nomorUrut < 1) {
            $nomorUrut = 1;
        }
        $nomorPeserta = $institution->buildNomorPeserta($nomorUrut, $session->scheduled_start_at ?? $session->started_at);

        $pdf = DomPDF::loadView('exam.participant_card', [
            'participant' => $exam_participant,
            'exam' => $exam,
            'session' => $session,
            'nomor_peserta' => $nomorPeserta,
            'nomor_urut' => $nomorUrut,
        ]);
        $filename = 'Kartu_Peserta_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $exam_participant->student->name ?? $exam_participant->id) . '_' . ($exam_participant->student->nis ?? $exam_participant->id) . '.pdf';
        if ($request->boolean('preview')) {
            return $pdf->stream($filename);
        }
        return $pdf->download($filename);
    }

    /**
     * Print all participant cards for a session. A4 landscape, 8 cards per page, with student photo from documents.
     */
    public function printSessionCards(Request $request, ExamSession $exam_session)
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $participants = $exam_session->participants()
            ->with(['student.class', 'student.documents'])
            ->orderByRaw('COALESCE(participant_order, 999999) ASC')
            ->orderBy('id')
            ->get();
        $exam = $exam_session->exam;
        $exam->load('institution');
        $session = $exam_session;
        $institution = $exam->institution;
        $dateSource = $session->scheduled_start_at ?? $session->started_at;

        $cards = [];
        $nomorUrutInSession = 1;
        foreach ($participants as $p) {
            $student = $p->student;
            $photoPath = null;
            $photoBase64 = null;
            if ($student && $student->relationLoaded('documents') && $student->documents->isNotEmpty()) {
                $photoDoc = $student->documents->first(function ($d) {
                    return $d->mime_type && str_starts_with($d->mime_type, 'image/');
                });
                if ($photoDoc && $photoDoc->file_path && Storage::disk('public')->exists($photoDoc->file_path)) {
                    $fullPath = Storage::disk('public')->path($photoDoc->file_path);
                    $photoPath = $fullPath;
                    $bin = @file_get_contents($fullPath);
                    if ($bin !== false) {
                        $photoBase64 = 'data:' . ($photoDoc->mime_type ?? 'image/jpeg') . ';base64,' . base64_encode($bin);
                    }
                }
            }
            $orderForNomor = $p->participant_order ?? $nomorUrutInSession;
            $nomorPeserta = $institution->buildNomorPeserta((int) $orderForNomor, $dateSource);
            $cards[] = [
                'photo_path' => $photoPath,
                'photo_base64' => $photoBase64,
                'nomor_peserta' => $nomorPeserta,
                'nama' => $student->name ?? '–',
                'kelas' => $student->class->name ?? $student->class ?? '–',
                'sesi' => $session->name ?? '–',
                'nomor_urut' => $nomorUrutInSession,
            ];
            $nomorUrutInSession++;
        }

        $pages = array_chunk(array_pad($cards, (int) ceil(max(1, count($cards)) / 8) * 8, null), 8);
        if (empty($cards)) {
            return response()->json(['message' => 'Tidak ada peserta dalam sesi ini.'], 422);
        }

        $pdf = DomPDF::loadView('exam.participant_cards_a4', [
            'pages' => $pages,
            'exam' => $exam,
        ])->setPaper('a4', 'landscape');
        $filename = 'Kartu_Peserta_Sesi_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $session->name) . '_' . date('Y-m-d_His') . '.pdf';
        if ($request->boolean('preview')) {
            return $pdf->stream($filename);
        }
        return $pdf->download($filename);
    }
}

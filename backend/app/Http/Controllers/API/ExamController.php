<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StoreExamRequest;
use App\Http\Requests\UpdateExamRequest;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Models\QuestionBank;
use App\Models\Subject;
use App\Services\ExamQuestionSnapshot;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExamController extends Controller
{
    use ResolvesInstitution;
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $query = Exam::forInstitution($institutionId)->with(['subject', 'sessions' => fn ($q) => $q->withCount('participants')]);

            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->get('subject_id'));
            }
            if ($request->filled('exam_period_id')) {
                $query->where('exam_period_id', $request->get('exam_period_id'));
            }
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where('name', 'like', '%' . $search . '%');
            }

            $query->orderByDesc('created_at');
            $perPage = min($request->get('per_page', 15), 100);
            $items = $query->paginate($perPage);

            return ExamResource::collection($items);
        } catch (\Exception $e) {
            Log::error('Exam index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data ujian.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    public function store(StoreExamRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $data['created_by'] = $request->user()->id;
            if (!isset($data['duration_minutes']) || $data['duration_minutes'] === null) {
                $data['duration_minutes'] = 60;
            }
            $exam = Exam::create($data);
            $exam->load(['subject']);

            return (new ExamResource($exam))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('Exam store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal membuat ujian.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    public function show(Request $request, Exam $exam): ExamResource|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId || $exam->institution_id != $institutionId) {
                return response()->json(['message' => 'Ujian tidak ditemukan.'], 404);
            }
            $exam->load([
                'subject',
                'examPeriod',
                'sessions' => fn ($q) => $q->withCount('participants'),
                'examQuestions.questionBank.bankSoal',
                'examQuestions.questionBank.stimulus',
            ]);

            return new ExamResource($exam);
        } catch (\Throwable $e) {
            Log::error('Exam show failed', [
                'exam_id' => $exam->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal memuat ujian.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get exam by code (for URL-friendly routing). Code is unique per institution.
     */
    public function showByCode(Request $request, string $code): ExamResource|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $exam = Exam::forInstitution($institutionId)
                ->where('code', $code)
                ->first();
            if (!$exam) {
                return response()->json(['message' => 'Ujian tidak ditemukan.'], 404);
            }
            $exam->load([
                'subject',
                'examPeriod',
                'sessions' => fn ($q) => $q->withCount('participants'),
                'examQuestions.questionBank.bankSoal',
                'examQuestions.questionBank.stimulus',
            ]);

            return new ExamResource($exam);
        } catch (\Throwable $e) {
            Log::error('Exam showByCode failed', [
                'code' => $code,
                'error' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return response()->json([
                'message' => 'Gagal memuat ujian.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function update(UpdateExamRequest $request, Exam $exam): ExamResource|JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId || $exam->institution_id != $institutionId) {
            return response()->json(['message' => 'Ujian tidak ditemukan.'], 404);
        }
        $exam->update($request->validated());
        $exam->load(['subject']);
        return new ExamResource($exam);
    }

    public function destroy(Request $request, Exam $exam): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId || $exam->institution_id != $institutionId) {
            return response()->json(['message' => 'Ujian tidak ditemukan.'], 404);
        }
        $exam->delete();
        return response()->json(['message' => 'Ujian dihapus.']);
    }

    /**
     * Mata pelajaran yang siap diujikan (punya minimal 1 soal di bank soal).
     */
    public function subjectsReady(Request $request): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }
        $subjects = Subject::forInstitution($institutionId)
            ->withCount('questionBanks')
            ->having('question_banks_count', '>', 0)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'institution_id']);
        $data = $subjects->map(fn ($s) => [
            'id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'questions_count' => (int) $s->question_banks_count,
        ]);
        return response()->json(['data' => $data]);
    }

    /**
     * Attach questions to exam (replace existing package).
     * Soal disalin ke paket ujian; edit di bank tidak mengubah ujian ini.
     * Tingkat bank tidak membatasi — ujian kelas 9 boleh memakai soal dari rak kelas 7.
     * Mapel soal harus sama dengan mapel ujian (jika ujian punya mapel).
     */
    public function attachQuestions(Request $request, Exam $exam): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId || $exam->institution_id != $institutionId) {
            return response()->json(['message' => 'Ujian tidak ditemukan.'], 404);
        }
        if ($exam->questionsAreLocked()) {
            return response()->json([
                'message' => 'Paket soal terkunci karena sesi sudah berjalan atau selesai. Reset sesi ke draf jika paket perlu diubah.',
            ], 422);
        }
        $request->validate([
            'question_bank_ids' => 'required|array|min:1',
            'question_bank_ids.*' => 'integer|exists:question_bank,id',
        ]);
        $ids = array_values(array_unique(array_map('intval', $request->input('question_bank_ids'))));

        $questions = QuestionBank::query()
            ->where('institution_id', $institutionId)
            ->whereIn('id', $ids)
            ->with(['options', 'stimulus', 'bankSoal'])
            ->get()
            ->keyBy('id');

        foreach ($ids as $qbId) {
            if (! $questions->has($qbId)) {
                return response()->json([
                    'message' => 'Beberapa soal tidak ditemukan atau bukan milik institusi Anda.',
                ], 422);
            }
            if ($exam->subject_id && (int) $questions->get($qbId)->subject_id !== (int) $exam->subject_id) {
                return response()->json([
                    'message' => 'Soal harus dari mata pelajaran yang sama dengan ujian. Tingkat kelas tidak membatasi.',
                ], 422);
            }
            if (($questions->get($qbId)->status ?? QuestionBank::STATUS_READY) === QuestionBank::STATUS_DRAFT) {
                return response()->json([
                    'message' => 'Soal berstatus draf tidak bisa masuk paket ujian. Tandai siap di bank soal terlebih dahulu.',
                ], 422);
            }
        }

        // Rapikan: soal se-stimulus digandeng berurutan (urut sort_order bank).
        $ids = ExamService::orderKeepingStimulusGroups($ids, $questions);

        DB::transaction(function () use ($exam, $ids, $questions) {
            $exam->examQuestions()->delete();
            foreach ($ids as $i => $qbId) {
                $exam->examQuestions()->create([
                    'question_bank_id' => $qbId,
                    'sort_order' => $i,
                    'snapshot' => ExamQuestionSnapshot::capture($questions->get($qbId)),
                ]);
            }
        });

        $exam->load(['subject', 'sessions', 'examQuestions.questionBank.bankSoal', 'examQuestions.questionBank.stimulus']);
        return response()->json(['message' => 'Paket soal ujian disimpan.', 'exam' => new ExamResource($exam)]);
    }

    /**
     * Usulan ID soal untuk paket: set stimulus lengkap, atau acak N (unit stimulus dijaga).
     * Tidak menempel ke ujian — frontend menggabungkan ke keranjang lalu attach.
     */
    public function suggestQuestions(Request $request, Exam $exam): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (! $institutionId || $exam->institution_id != $institutionId) {
            return response()->json(['message' => 'Ujian tidak ditemukan.'], 404);
        }

        $data = $request->validate([
            'mode' => 'required|in:stimulus,random',
            'stimulus_id' => 'required_if:mode,stimulus|nullable|integer|exists:question_stimuli,id',
            'count' => 'required_if:mode,random|nullable|integer|min:1|max:200',
            'bank_soal_id' => 'nullable|integer|exists:bank_soal,id',
            'type' => 'nullable|string|in:pg,pg_kompleks,matching,isian,uraian',
            'grade' => 'nullable|integer|min:1|max:12',
            'difficulty' => 'nullable|string|in:mudah,sedang,sulit',
            'tag' => 'nullable|string|max:40',
            'cognitive_level' => 'nullable|string|in:c1,c2,c3,c4,c5,c6',
            'topic' => 'nullable|string|max:120',
            'exclude_ids' => 'nullable|array',
            'exclude_ids.*' => 'integer',
        ]);

        $query = QuestionBank::query()
            ->where('institution_id', $institutionId)
            ->where(function ($q) {
                $q->where('status', QuestionBank::STATUS_READY)->orWhereNull('status');
            })
            ->with(['stimulus', 'bankSoal']);

        if ($exam->subject_id) {
            $query->where('subject_id', $exam->subject_id);
        }
        if (! empty($data['bank_soal_id'])) {
            $query->where('bank_soal_id', $data['bank_soal_id']);
        }
        if (! empty($data['type'])) {
            $query->where('type', $data['type']);
        }
        if (! empty($data['grade'])) {
            $query->whereHas('bankSoal', fn ($q) => $q->where('grade', $data['grade']));
        }
        if (! empty($data['difficulty'])) {
            $query->where('difficulty', $data['difficulty']);
        }
        if (! empty($data['cognitive_level'])) {
            $query->where('cognitive_level', $data['cognitive_level']);
        }
        if (! empty($data['tag'])) {
            $query->whereJsonContains('tags', mb_strtolower(trim((string) $data['tag'])));
        }
        if (! empty($data['topic'])) {
            $query->where('topic', trim((string) $data['topic']));
        }
        $exclude = array_values(array_unique(array_map('intval', $data['exclude_ids'] ?? [])));
        if ($exclude !== []) {
            $query->whereNotIn('id', $exclude);
        }

        if ($data['mode'] === 'stimulus') {
            $query->where('stimulus_id', $data['stimulus_id'])
                ->orderBy('sort_order')
                ->orderBy('id');
            $questions = $query->get();
            if ($questions->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada soal untuk stimulus ini (atau sudah dipilih semua).',
                    'question_bank_ids' => [],
                ], 422);
            }
            $ids = $questions->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

            return response()->json([
                'question_bank_ids' => $ids,
                'count' => count($ids),
                'mode' => 'stimulus',
                'stimulus_id' => (int) $data['stimulus_id'],
            ]);
        }

        // random: bangun unit (set stimulus / soal tunggal), acak unit, ambil sampai count soal
        $candidates = $query->orderBy('sort_order')->orderBy('id')->get();
        if ($candidates->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada soal yang cocok untuk diacak.',
                'question_bank_ids' => [],
            ], 422);
        }

        $byStimulus = [];
        $singles = [];
        foreach ($candidates as $q) {
            if ($q->stimulus_id) {
                $byStimulus[(int) $q->stimulus_id][] = $q;
            } else {
                $singles[] = $q;
            }
        }
        $units = [];
        foreach ($byStimulus as $group) {
            usort($group, fn ($a, $b) => ((int) $a->sort_order <=> (int) $b->sort_order) ?: ($a->id <=> $b->id));
            $units[] = array_map(fn ($q) => (int) $q->id, $group);
        }
        foreach ($singles as $q) {
            $units[] = [(int) $q->id];
        }
        shuffle($units);

        $picked = [];
        $target = (int) $data['count'];
        foreach ($units as $unit) {
            if (count($picked) >= $target) {
                break;
            }
            // jika unit stimulus melebihi sisa kuota, skip unit itu (jaga set utuh)
            if (count($unit) > 1 && count($picked) + count($unit) > $target) {
                continue;
            }
            foreach ($unit as $qid) {
                $picked[] = $qid;
            }
        }
        // jika masih kurang (karena skip set besar), isi dengan single yang tersisa
        if (count($picked) < $target) {
            $pickedSet = array_fill_keys($picked, true);
            foreach ($units as $unit) {
                if (count($unit) !== 1) {
                    continue;
                }
                $qid = $unit[0];
                if (isset($pickedSet[$qid])) {
                    continue;
                }
                $picked[] = $qid;
                $pickedSet[$qid] = true;
                if (count($picked) >= $target) {
                    break;
                }
            }
        }

        return response()->json([
            'question_bank_ids' => $picked,
            'count' => count($picked),
            'mode' => 'random',
            'requested' => $target,
        ]);
    }
}

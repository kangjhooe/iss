<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Extracurricular;
use App\Models\Institution;
use App\Models\MemorizationDeposit;
use App\Models\MemorizationTarget;
use App\Models\QuranSurah;
use App\Models\Student;
use App\Services\MemorizationService;
use App\Support\ExtracurricularAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ExtracurricularMemorizationController extends Controller
{
    public function __construct(private MemorizationService $service) {}

    private function denyUnlessCanAccess(Request $request, Extracurricular $extracurricular): ?JsonResponse
    {
        if (!ExtracurricularAccess::canAccess($request->user(), $extracurricular)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return null;
    }

    private function denyUnlessMemorization(Extracurricular $extracurricular): ?JsonResponse
    {
        if (!$extracurricular->isMemorizationMode()) {
            return response()->json([
                'message' => 'Ekskul ini memakai mode penilaian standar, bukan hapalan.',
            ], 422);
        }

        return null;
    }

    private function activeSemesterId(int $institutionId): ?int
    {
        return Institution::find($institutionId)?->active_semester_id;
    }

    private function employeeId(Request $request, Extracurricular $extracurricular): ?int
    {
        return ExtracurricularAccess::employeeFor(
            $request->user(),
            (int) $extracurricular->institution_id
        )?->id;
    }

    public function listSurahs(): JsonResponse
    {
        $surahs = QuranSurah::query()
            ->orderBy('number')
            ->get(['number', 'name_ar', 'name_id', 'name_latin', 'ayah_count', 'revelation_type']);

        return response()->json(['data' => $surahs]);
    }

    public function showSurah(int $number): JsonResponse
    {
        $surah = QuranSurah::query()->where('number', $number)->first();
        if (!$surah) {
            return response()->json(['message' => 'Surat tidak ditemukan.'], 404);
        }

        $ayahs = $surah->ayahs()
            ->orderBy('ayah_number')
            ->get(['ayah_number', 'text_ar', 'text_id']);

        return response()->json([
            'data' => [
                'number' => $surah->number,
                'name_ar' => $surah->name_ar,
                'name_id' => $surah->name_id,
                'name_latin' => $surah->name_latin,
                'ayah_count' => $surah->ayah_count,
                'ayahs' => $ayahs,
            ],
        ]);
    }

    public function listTargets(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ($resp = $this->denyUnlessMemorization($extracurricular)) {
            return $resp;
        }

        $targets = MemorizationTarget::query()
            ->where('extracurricular_id', $extracurricular->id)
            ->with(['schoolClass:id,name,grade', 'student:id,name,nis'])
            ->orderByRaw("CASE scope WHEN 'extracurricular' THEN 1 WHEN 'class' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->get()
            ->map(fn (MemorizationTarget $t) => $this->targetPayload($t));

        return response()->json(['data' => $targets]);
    }

    public function storeTarget(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ($resp = $this->denyUnlessMemorization($extracurricular)) {
            return $resp;
        }

        $validator = Validator::make($request->all(), [
            'scope' => ['required', Rule::in([
                MemorizationTarget::SCOPE_EXTRACURRICULAR,
                MemorizationTarget::SCOPE_CLASS,
                MemorizationTarget::SCOPE_STUDENT,
            ])],
            'class_id' => 'nullable|exists:class,id|required_if:scope,class',
            'student_id' => 'nullable|exists:student,id|required_if:scope,student',
            'semester_id' => 'nullable|exists:semesters,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'name' => 'nullable|string|max:255',
            'template' => 'nullable|string|in:juz30,custom',
            'items' => 'nullable|array|min:1',
            'items.*.surah' => 'required_with:items|integer|min:1|max:114',
            'items.*.from' => 'nullable|integer|min:1',
            'items.*.to' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $items = $data['items'] ?? null;
        if (($data['template'] ?? null) === 'juz30' || (!$items && ($data['template'] ?? 'juz30') === 'juz30')) {
            $items = MemorizationService::juz30Items();
            $data['name'] = $data['name'] ?? 'Juz 30';
        }
        if (!$items) {
            return response()->json(['message' => 'Items target wajib diisi.'], 422);
        }

        $scope = $data['scope'];
        $target = MemorizationTarget::create([
            'institution_id' => $extracurricular->institution_id,
            'extracurricular_id' => $extracurricular->id,
            'scope' => $scope,
            'class_id' => $scope === MemorizationTarget::SCOPE_CLASS ? ($data['class_id'] ?? null) : null,
            'student_id' => $scope === MemorizationTarget::SCOPE_STUDENT ? ($data['student_id'] ?? null) : null,
            'semester_id' => $data['semester_id'] ?? $this->activeSemesterId((int) $extracurricular->institution_id),
            'academic_year_id' => $data['academic_year_id'] ?? null,
            'name' => $data['name'] ?? null,
            'items' => $items,
        ]);

        $target->load(['schoolClass:id,name,grade', 'student:id,name,nis']);

        return response()->json([
            'message' => 'Target hapalan disimpan.',
            'data' => $this->targetPayload($target),
        ], 201);
    }

    public function updateTarget(Request $request, Extracurricular $extracurricular, MemorizationTarget $target): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ((int) $target->extracurricular_id !== (int) $extracurricular->id) {
            return response()->json(['message' => 'Target tidak ditemukan.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'template' => 'nullable|string|in:juz30,custom',
            'items' => 'nullable|array|min:1',
            'items.*.surah' => 'required_with:items|integer|min:1|max:114',
            'items.*.from' => 'nullable|integer|min:1',
            'items.*.to' => 'nullable|integer|min:1',
            'semester_id' => 'nullable|exists:semesters,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (($data['template'] ?? null) === 'juz30') {
            $data['items'] = MemorizationService::juz30Items();
            $data['name'] = $data['name'] ?? 'Juz 30';
        }

        $target->fill(collect($data)->only(['name', 'items', 'semester_id'])->filter(fn ($v) => $v !== null)->all());
        if (isset($data['items'])) {
            $target->items = $data['items'];
        }
        if (array_key_exists('name', $data)) {
            $target->name = $data['name'];
        }
        $target->save();
        $target->load(['schoolClass:id,name,grade', 'student:id,name,nis']);

        return response()->json([
            'message' => 'Target diperbarui.',
            'data' => $this->targetPayload($target),
        ]);
    }

    public function destroyTarget(Request $request, Extracurricular $extracurricular, MemorizationTarget $target): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ((int) $target->extracurricular_id !== (int) $extracurricular->id) {
            return response()->json(['message' => 'Target tidak ditemukan.'], 404);
        }

        $target->delete();

        return response()->json(['message' => 'Target dihapus.']);
    }

    public function rosterProgress(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ($resp = $this->denyUnlessMemorization($extracurricular)) {
            return $resp;
        }

        $semesterId = $request->filled('semester_id')
            ? (int) $request->get('semester_id')
            : $this->activeSemesterId((int) $extracurricular->institution_id);

        $rows = $this->service->rosterProgress($extracurricular, $semesterId);

        return response()->json([
            'data' => $rows,
            'meta' => [
                'semester_id' => $semesterId,
                'assessment_mode' => $extracurricular->assessment_mode,
            ],
        ]);
    }

    public function studentProgress(Request $request, Extracurricular $extracurricular, int $studentId): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ($resp = $this->denyUnlessMemorization($extracurricular)) {
            return $resp;
        }

        $student = Student::with('class')->find($studentId);
        if (!$student) {
            return response()->json(['message' => 'Siswa tidak ditemukan.'], 404);
        }

        $semesterId = $request->filled('semester_id')
            ? (int) $request->get('semester_id')
            : $this->activeSemesterId((int) $extracurricular->institution_id);

        $progress = $this->service->studentProgress($extracurricular, $student, $semesterId);

        return response()->json([
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'class' => \App\Support\StudentRosterSort::classArray($student),
                ],
                'target' => $progress['target'] ? $this->targetPayload($progress['target']) : null,
                'target_ayahs' => $progress['target_ayahs'],
                'deposited_ayahs' => $progress['deposited_ayahs'],
                'deposited_total' => $progress['deposited_total'],
                'percent' => $progress['percent'],
                'surahs' => $progress['surahs'],
            ],
        ]);
    }

    public function surahChecklist(Request $request, Extracurricular $extracurricular, int $studentId, int $surahNumber): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ($resp = $this->denyUnlessMemorization($extracurricular)) {
            return $resp;
        }

        try {
            $data = $this->service->surahChecklist($extracurricular, $studentId, $surahNumber);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Surat tidak ditemukan.'], 404);
        }

        return response()->json(['data' => $data]);
    }

    public function syncChecklist(Request $request, Extracurricular $extracurricular, int $studentId, int $surahNumber): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ($resp = $this->denyUnlessMemorization($extracurricular)) {
            return $resp;
        }

        $validator = Validator::make($request->all(), [
            'ayah_numbers' => 'required|array',
            'ayah_numbers.*' => 'integer|min:1',
            'replace' => 'nullable|boolean',
            'ayah_from' => 'nullable|integer|min:1',
            'ayah_to' => 'nullable|integer|min:1',
            'quality' => 'nullable|string|in:lancar,kurang,mengulang',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        try {
            $checklist = $this->service->syncSurahChecklist(
                $extracurricular,
                $studentId,
                $surahNumber,
                $data['ayah_numbers'],
                $this->employeeId($request, $extracurricular),
                $data['quality'] ?? null,
                (bool) ($data['replace'] ?? true),
                $data['ayah_from'] ?? null,
                $data['ayah_to'] ?? null
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'message' => 'Checklist hapalan disimpan.',
            'data' => $checklist,
        ]);
    }

    public function storeDeposit(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ($resp = $this->denyUnlessMemorization($extracurricular)) {
            return $resp;
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:student,id',
            'surah_number' => 'required|integer|min:1|max:114',
            'ayah_from' => 'required|integer|min:1',
            'ayah_to' => 'required|integer|min:1',
            'quality' => 'nullable|string|in:lancar,kurang,mengulang',
            'notes' => 'nullable|string|max:2000',
            'session_id' => 'nullable|exists:extracurricular_sessions,id',
            'deposited_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        try {
            $deposit = $this->service->recordDeposit(
                $extracurricular,
                $validator->validated(),
                $this->employeeId($request, $extracurricular)
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'message' => 'Setoran tercatat.',
            'data' => $deposit,
        ], 201);
    }

    public function listDeposits(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ($resp = $this->denyUnlessMemorization($extracurricular)) {
            return $resp;
        }

        $query = MemorizationDeposit::query()
            ->where('extracurricular_id', $extracurricular->id)
            ->with(['student:id,name,nis', 'surah', 'recorder:id,name'])
            ->orderByDesc('deposited_at')
            ->orderByDesc('id');

        if ($request->filled('student_id')) {
            $query->where('student_id', (int) $request->get('student_id'));
        }

        $perPage = min((int) $request->get('per_page', 30), 100);

        return response()->json($query->paginate($perPage));
    }

    private function targetPayload(MemorizationTarget $t): array
    {
        return [
            'id' => $t->id,
            'scope' => $t->scope,
            'class_id' => $t->class_id,
            'class' => $t->schoolClass ? [
                'id' => $t->schoolClass->id,
                'name' => $t->schoolClass->name,
                'grade' => $t->schoolClass->grade,
            ] : null,
            'student_id' => $t->student_id,
            'student' => $t->student ? [
                'id' => $t->student->id,
                'name' => $t->student->name,
                'nis' => $t->student->nis,
            ] : null,
            'semester_id' => $t->semester_id,
            'academic_year_id' => $t->academic_year_id,
            'name' => $t->name,
            'items' => $t->items,
            'target_ayahs' => $this->service->countTargetAyahs($t->items ?? []),
            'created_at' => $t->created_at?->toIso8601String(),
        ];
    }
}

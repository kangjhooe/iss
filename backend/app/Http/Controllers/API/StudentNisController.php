<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNisNumberingRequest;
use App\Http\Resources\StudentResource;
use App\Models\Institution;
use App\Models\Student;
use App\Services\LocalNisService;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class StudentNisController extends Controller
{
    public function __construct(protected LocalNisService $localNisService)
    {
    }

    public function show(Request $request)
    {
        $institution = $this->resolveInstitution($request);
        if (!$institution) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        $settings = $this->localNisService->settingsFor($institution);
        $preview = null;
        $previewError = null;
        $nextSeq = 1;
        try {
            $preview = $this->localNisService->preview($institution);
            $nextSeq = $this->localNisService->nextSeq($institution, $settings);
        } catch (InvalidArgumentException $e) {
            $previewError = $e->getMessage();
        }

        return response()->json([
            'data' => [
                'settings' => $settings,
                'presets' => $this->localNisService->presetOptions(),
                'preview' => $preview,
                'preview_error' => $previewError,
                'missing_nis_count' => $this->localNisService->missingNisCount($institution->id),
                'year_code' => $this->localNisService->yearCode($institution),
                'next_seq' => $nextSeq,
            ],
        ]);
    }

    public function update(UpdateNisNumberingRequest $request)
    {
        $institution = $this->resolveInstitution($request);
        if (!$institution) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        $settings = $this->localNisService->normalize($request->validated());
        try {
            $preview = $this->localNisService->preview($institution, $settings);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $institution->nis_numbering = $settings;
        $institution->save();

        Log::info('NIS numbering settings updated', [
            'institution_id' => $institution->id,
            'user_id' => $request->user()->id,
            'settings' => $settings,
        ]);

        return response()->json([
            'message' => 'Format NIS lokal disimpan.',
            'data' => [
                'settings' => $settings,
                'presets' => $this->localNisService->presetOptions(),
                'preview' => $preview,
                'preview_error' => null,
                'missing_nis_count' => $this->localNisService->missingNisCount($institution->id),
                'year_code' => $this->localNisService->yearCode($institution),
                'next_seq' => $this->localNisService->nextSeq($institution, $settings),
            ],
        ]);
    }

    public function generateOne(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        if (!$this->userCanAccessStudent($request, $student)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->localNisService->hasNis($student->nis)) {
            return response()->json([
                'message' => 'Siswa ini sudah memiliki NIS.',
                'data' => new StudentResource($student->loadMissing(['institution', 'class', 'academicYear'])),
            ], 422);
        }

        try {
            $this->localNisService->assignIfEmpty($student);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $student->refresh();

        Log::info('Local NIS generated for student', [
            'student_id' => $student->id,
            'nis' => $student->nis,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'NIS lokal berhasil digenerate: '.$student->nis,
            'data' => new StudentResource($student->loadMissing(['institution', 'class', 'academicYear'])),
        ]);
    }

    public function generateBulk(Request $request)
    {
        $user = $request->user();
        if (!$user->isAdminOrSuperAdmin() && !$user->isInstitutionAdmin()
            && !$user->hasModuleAccess('student')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'start_seq' => ['nullable', 'integer', 'min:1', 'max:99999999'],
        ], [
            'start_seq.min' => 'Nomor urut awal minimal 1.',
            'start_seq.max' => 'Nomor urut awal terlalu besar.',
        ]);

        $institution = $this->resolveInstitution($request);
        if (!$institution) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        try {
            $result = $this->localNisService->assignMany(
                $institution->id,
                $validated['student_ids'],
                (int) ($validated['limit'] ?? 500),
                isset($validated['start_seq']) ? (int) $validated['start_seq'] : null
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        Log::info('Bulk local NIS generated', [
            'institution_id' => $institution->id,
            'user_id' => $user->id,
            'result' => [
                'processed' => $result['processed'],
                'assigned' => $result['assigned'],
                'skipped' => $result['skipped'],
            ],
        ]);

        $message = $result['assigned'] > 0
            ? sprintf('%d siswa mendapat NIS lokal.', $result['assigned'])
            : 'Tidak ada siswa yang perlu digenerate NIS.';

        return response()->json([
            'message' => $message,
            'data' => $result,
        ]);
    }

    public function previewGenerate(Request $request)
    {
        $user = $request->user();
        if (!$user->isAdminOrSuperAdmin() && !$user->isInstitutionAdmin()
            && !$user->hasModuleAccess('student')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'start_seq' => ['nullable', 'integer', 'min:1', 'max:99999999'],
        ], [
            'start_seq.min' => 'Nomor urut awal minimal 1.',
            'start_seq.max' => 'Nomor urut awal terlalu besar.',
        ]);

        $institution = $this->resolveInstitution($request);
        if (!$institution) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        try {
            $result = $this->localNisService->previewAssignments(
                $institution->id,
                $validated['student_ids'] ?? null,
                (int) ($validated['limit'] ?? 500),
                isset($validated['start_seq']) ? (int) $validated['start_seq'] : null
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Ini pratinjau. NIS belum disimpan ke data siswa.',
            'data' => $result,
        ]);
    }

    private function resolveInstitution(Request $request): ?Institution
    {
        $user = $request->user();
        $institutionId = InstitutionContext::resolveForUser(
            $user,
            $request,
            $request->filled('institution_id') ? $request->get('institution_id') : null
        );

        if (!$institutionId) {
            return null;
        }

        return Institution::with('activeAcademicYear')->find($institutionId);
    }

    private function userCanAccessStudent(Request $request, Student $student): bool
    {
        $user = $request->user();
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        $institution = $this->resolveInstitution($request);

        return $institution && (int) $student->institution_id === (int) $institution->id;
    }
}

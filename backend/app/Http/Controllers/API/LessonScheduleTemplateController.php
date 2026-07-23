<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\LessonScheduleTemplate;
use App\Services\LessonScheduleTemplateService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class LessonScheduleTemplateController extends Controller
{
    public function __construct(
        protected LessonScheduleTemplateService $templateService
    ) {}

    private function resolveInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    private function resolveSemesterId(Request $request, int $institutionId): ?int
    {
        $semesterId = $request->get('semester_id');
        if ($semesterId) {
            return (int) $semesterId;
        }

        $institution = Institution::find($institutionId);

        return $institution?->active_semester_id ? (int) $institution->active_semester_id : null;
    }

    private function toPayload(LessonScheduleTemplate $template): array
    {
        $days = LessonScheduleTemplate::normalizeDays($template->days ?? []);

        return [
            'id' => $template->id,
            'institution_id' => (int) $template->institution_id,
            'semester_id' => (int) $template->semester_id,
            'name' => $template->name ?: 'Template',
            'days' => $days,
            'max_periods' => collect($days)->max('periods') ?: 0,
            'is_persisted' => (bool) $template->exists,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $semesterId = $this->resolveSemesterId($request, $institutionId);
            if (! $semesterId) {
                return response()->json(['message' => 'Semester wajib dipilih (semester_id).'], 422);
            }

            $templates = $this->templateService->listForSemester($institutionId, $semesterId);

            return response()->json([
                'data' => $templates->map(fn (LessonScheduleTemplate $t) => $this->toPayload($t))->values(),
            ]);
        } catch (\Exception $e) {
            Log::error('LessonScheduleTemplate index failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil daftar template jadwal.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Backward-compatible: returns default template (or virtual default).
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $semesterId = $this->resolveSemesterId($request, $institutionId);
            if (! $semesterId) {
                return response()->json(['message' => 'Semester wajib dipilih (semester_id).'], 422);
            }

            $templateId = $request->get('id') ?? $request->get('template_id');
            if ($templateId) {
                $template = $this->templateService->findForInstitution($institutionId, (int) $templateId);
                $template->days = LessonScheduleTemplate::normalizeDays($template->days ?? []);
            } else {
                $template = $this->templateService->getOrDefault($institutionId, $semesterId);
            }

            return response()->json([
                'data' => $this->toPayload($template),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Template tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('LessonScheduleTemplate show failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil template jadwal.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $validated = $request->validate([
                'semester_id' => 'nullable|integer|exists:semesters,id',
                'name' => 'required|string|max:100',
                'days' => 'nullable|array|min:1',
                'days.*.day_of_week' => 'required_with:days|integer|min:1|max:7',
                'days.*.periods' => 'nullable|integer|min:0|max:20',
                'days.*.is_holiday' => 'nullable|boolean',
            ]);

            $semesterId = isset($validated['semester_id'])
                ? (int) $validated['semester_id']
                : $this->resolveSemesterId($request, $institutionId);
            if (! $semesterId) {
                return response()->json(['message' => 'Semester wajib dipilih (semester_id).'], 422);
            }

            $days = $validated['days'] ?? LessonScheduleTemplate::defaultDays();

            $template = $this->templateService->create(
                $institutionId,
                $semesterId,
                $validated['name'],
                $days
            );

            return response()->json([
                'message' => 'Template jadwal berhasil dibuat.',
                'data' => $this->toPayload($template),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'lesson_schedule_templates_inst_sem_name_unique')) {
                return response()->json(['message' => 'Nama template sudah dipakai di semester ini.'], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            Log::error('LessonScheduleTemplate store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal membuat template jadwal.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $template = $this->templateService->findForInstitution($institutionId, $id);

            $validated = $request->validate([
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('lesson_schedule_templates', 'name')
                        ->where('institution_id', $institutionId)
                        ->where('semester_id', $template->semester_id)
                        ->ignore($template->id),
                ],
                'days' => 'sometimes|required|array|min:1',
                'days.*.day_of_week' => 'required_with:days|integer|min:1|max:7',
                'days.*.periods' => 'nullable|integer|min:0|max:20',
                'days.*.is_holiday' => 'nullable|boolean',
            ]);

            $template = $this->templateService->update($template, $validated);

            return response()->json([
                'message' => 'Template jadwal berhasil disimpan.',
                'data' => $this->toPayload($template),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Template tidak ditemukan.'], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'lesson_schedule_templates_inst_sem_name_unique')) {
                return response()->json(['message' => 'Nama template sudah dipakai di semester ini.'], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            Log::error('LessonScheduleTemplate update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menyimpan template jadwal.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $template = $this->templateService->findForInstitution($institutionId, $id);
            $this->templateService->delete($template);

            return response()->json(['message' => 'Template jadwal berhasil dihapus.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Template tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('LessonScheduleTemplate destroy failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menghapus template jadwal.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function previewAssignToClass(Request $request, int $classId): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $validated = $request->validate([
                'lesson_schedule_template_id' => 'nullable|integer|exists:lesson_schedule_templates,id',
            ]);

            $preview = $this->templateService->previewAssignToClass(
                $institutionId,
                $classId,
                isset($validated['lesson_schedule_template_id'])
                    ? (int) $validated['lesson_schedule_template_id']
                    : null
            );

            return response()->json(['data' => $preview]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Kelas atau template tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('LessonScheduleTemplate previewAssignToClass failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memeriksa dampak ganti template.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function assignToClass(Request $request, int $classId): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $validated = $request->validate([
                'lesson_schedule_template_id' => 'nullable|integer|exists:lesson_schedule_templates,id',
                'prune_out_of_bounds' => 'nullable|boolean',
            ]);

            $result = $this->templateService->assignToClass(
                $institutionId,
                $classId,
                array_key_exists('lesson_schedule_template_id', $validated)
                    && $validated['lesson_schedule_template_id'] !== null
                    ? (int) $validated['lesson_schedule_template_id']
                    : null,
                (bool) ($validated['prune_out_of_bounds'] ?? false)
            );

            if (! empty($result['requires_prune'])) {
                return response()->json([
                    'message' => 'Ada slot jadwal yang tidak muat di template baru. Konfirmasi untuk menghapus slot tersebut.',
                    'requires_prune' => true,
                    'data' => [
                        'class_id' => $result['class']->id,
                        'affected_count' => $result['affected_count'],
                        'affected_slots' => $result['affected_slots'] ?? [],
                    ],
                ], 409);
            }

            $pruned = (int) ($result['pruned_count'] ?? 0);
            $message = $pruned > 0
                ? "Template kelas disimpan. {$pruned} slot di luar batas dihapus."
                : 'Template kelas berhasil disimpan.';

            return response()->json([
                'message' => $message,
                'data' => [
                    'class_id' => $result['class']->id,
                    'lesson_schedule_template_id' => $result['class']->lesson_schedule_template_id,
                    'pruned_count' => $pruned,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Kelas atau template tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('LessonScheduleTemplate assignToClass failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menetapkan template ke kelas.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Backward-compatible upsert for default template days.
     */
    public function upsert(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $validated = $request->validate([
                'semester_id' => 'nullable|integer|exists:semesters,id',
                'name' => 'nullable|string|max:100',
                'days' => 'required|array|min:1',
                'days.*.day_of_week' => 'required|integer|min:1|max:7',
                'days.*.periods' => 'nullable|integer|min:0|max:20',
                'days.*.is_holiday' => 'nullable|boolean',
            ], [
                'days.required' => 'Template hari wajib diisi.',
            ]);

            $semesterId = isset($validated['semester_id'])
                ? (int) $validated['semester_id']
                : $this->resolveSemesterId($request, $institutionId);
            if (! $semesterId) {
                return response()->json(['message' => 'Semester wajib dipilih (semester_id).'], 422);
            }

            $template = $this->templateService->upsert(
                $institutionId,
                $semesterId,
                $validated['days'],
                $validated['name'] ?? null
            );

            return response()->json([
                'message' => 'Template jadwal berhasil disimpan.',
                'data' => $this->toPayload($template),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('LessonScheduleTemplate upsert failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menyimpan template jadwal.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSemesterRequest;
use App\Http\Requests\UpdateSemesterRequest;
use App\Http\Resources\SemesterResource;
use App\Models\Institution;
use App\Models\Semester;
use App\Services\SemesterService;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function __construct(
        protected SemesterService $semesterService
    ) {}

    /**
     * Display a listing of semesters.
     * For non-super_admin users (incl. students), results are scoped to their institution's active academic year.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['academic_year_id', 'name', 'status']);
        $perPage = min($request->get('per_page', 15), 100);

        $user = $request->user();
        if ($user && !$user->isSuperAdmin()) {
            $institutionId = $user->institution_id ?? $user->studentProfile?->institution_id ?? null;
            if ($institutionId) {
                $institution = Institution::find($institutionId);
                if ($institution && $institution->active_academic_year_id) {
                    $filters['academic_year_id'] = $institution->active_academic_year_id;
                }
            }
        }

        $semesters = $this->semesterService->list($filters, $perPage);

        return SemesterResource::collection($semesters);
    }

    /**
     * Store a newly created semester.
     */
    public function store(StoreSemesterRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = $validated['status'] ?? 'Draft';

        $semester = $this->semesterService->create($validated);

        return response()->json([
            'message' => 'Semester berhasil ditambahkan',
            'data' => new SemesterResource($semester->load('academicYear')),
        ], 201);
    }

    /**
     * Display the specified semester.
     */
    public function show(Request $request, $id)
    {
        $semester = $this->semesterService->find($id);

        return new SemesterResource($semester);
    }

    /**
     * Update the specified semester.
     */
    public function update(UpdateSemesterRequest $request, $id)
    {
        $semester = Semester::findOrFail($id);

        $semester = $this->semesterService->update($semester, $request->validated());

        return response()->json([
            'message' => 'Semester berhasil diperbarui',
            'data' => new SemesterResource($semester),
        ]);
    }

    /**
     * Remove the specified semester.
     */
    public function destroy(Request $request, $id)
    {
        $semester = Semester::findOrFail($id);

        $this->semesterService->delete($semester);

        return response()->json([
            'message' => 'Semester berhasil dihapus',
        ]);
    }

    /**
     * Get semesters for a specific academic year.
     */
    public function byAcademicYear(Request $request, $academicYearId)
    {
        $semesters = $this->semesterService->getByAcademicYear($academicYearId);

        return SemesterResource::collection($semesters);
    }

    /**
     * Get active semester.
     */
    public function active(Request $request)
    {
        $semester = $this->semesterService->getActive();

        if (!$semester) {
            return response()->json([
                'message' => 'Tidak ada semester aktif',
                'data' => null,
            ], 404);
        }

        return new SemesterResource($semester->load('academicYear'));
    }

    /**
     * Get active semester for a specific academic year.
     */
    public function activeForAcademicYear(Request $request, $academicYearId)
    {
        $semester = $this->semesterService->getActiveForAcademicYear($academicYearId);

        if (!$semester) {
            return response()->json([
                'message' => 'Tidak ada semester aktif untuk tahun ajaran ini',
                'data' => null,
            ], 404);
        }

        return new SemesterResource($semester->load('academicYear'));
    }

    /**
     * Activate a semester.
     */
    public function activate(Request $request, $id)
    {
        $semester = Semester::findOrFail($id);

        $semester = $this->semesterService->activate($semester);

        return response()->json([
            'message' => 'Semester berhasil diaktifkan',
            'data' => new SemesterResource($semester->load('academicYear')),
        ]);
    }

    /**
     * Auto-generate Ganjil and Genap semesters for an academic year.
     */
    public function autoGenerate(Request $request, $academicYearId)
    {
        try {
            $semesters = $this->semesterService->autoGenerateForAcademicYear($academicYearId);

            return response()->json([
                'message' => 'Semester Ganjil dan Genap berhasil dibuat otomatis',
                'data' => SemesterResource::collection($semesters),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat semester otomatis: ' . $e->getMessage(),
            ], 500);
        }
    }
}

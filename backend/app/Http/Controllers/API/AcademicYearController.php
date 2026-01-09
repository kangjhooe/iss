<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademicYearRequest;
use App\Http\Requests\UpdateAcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use App\Services\AcademicYearService;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function __construct(
        protected AcademicYearService $academicYearService
    ) {}

    /**
     * Display a listing of academic years.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);
        $perPage = min($request->get('per_page', 15), 100);
        
        $academicYears = $this->academicYearService->list($filters, $perPage);

        return AcademicYearResource::collection($academicYears);
    }

    /**
     * Store a newly created academic year.
     */
    public function store(StoreAcademicYearRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = $validated['status'] ?? 'Draft';

        $academicYear = $this->academicYearService->create($validated);

        return response()->json([
            'message' => 'Tahun ajaran berhasil ditambahkan',
            'data' => new AcademicYearResource($academicYear->load('semesters')),
        ], 201);
    }

    /**
     * Display the specified academic year.
     */
    public function show(Request $request, $id)
    {
        $academicYear = $this->academicYearService->find($id);

        return new AcademicYearResource($academicYear);
    }

    /**
     * Update the specified academic year.
     */
    public function update(UpdateAcademicYearRequest $request, $id)
    {
        $academicYear = AcademicYear::findOrFail($id);

        $academicYear = $this->academicYearService->update($academicYear, $request->validated());

        return response()->json([
            'message' => 'Tahun ajaran berhasil diperbarui',
            'data' => new AcademicYearResource($academicYear),
        ]);
    }

    /**
     * Remove the specified academic year.
     */
    public function destroy(Request $request, $id)
    {
        $academicYear = AcademicYear::findOrFail($id);

        $this->academicYearService->delete($academicYear);

        return response()->json([
            'message' => 'Tahun ajaran berhasil dihapus',
        ]);
    }

    /**
     * Get active academic year.
     */
    public function active(Request $request)
    {
        $academicYear = $this->academicYearService->getActive();

        if (!$academicYear) {
            return response()->json([
                'message' => 'Tidak ada tahun ajaran aktif',
                'data' => null,
            ], 404);
        }

        return new AcademicYearResource($academicYear->load('semesters'));
    }

    /**
     * Get current academic year (based on date).
     */
    public function current(Request $request)
    {
        $academicYear = $this->academicYearService->getCurrent();

        if (!$academicYear) {
            return response()->json([
                'message' => 'Tidak ada tahun ajaran saat ini',
                'data' => null,
            ], 404);
        }

        return new AcademicYearResource($academicYear->load('semesters'));
    }

    /**
     * Activate an academic year.
     */
    public function activate(Request $request, $id)
    {
        $academicYear = AcademicYear::findOrFail($id);

        $academicYear = $this->academicYearService->activate($academicYear);

        return response()->json([
            'message' => 'Tahun ajaran berhasil diaktifkan',
            'data' => new AcademicYearResource($academicYear->load('semesters')),
        ]);
    }
}

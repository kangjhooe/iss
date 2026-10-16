<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesActiveInstitution;
use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryNeedAnalysisResource;
use App\Models\Institution;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryNeedAnalysis;
use App\Models\InventoryNeedLine;
use App\Services\InventoryNeedAnalysisService;
use App\Support\InventoryAccess;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class InventoryNeedAnalysisController extends Controller
{
    use ResolvesActiveInstitution;

    public function __construct(
        private InventoryNeedAnalysisService $service
    ) {}

    public function sources(Request $request)
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            return $denied;
        }

        $institutionId = $this->resolveInstitutionId($request);
        if (! $institutionId) {
            return response()->json(['data' => [
                'categories' => [],
                'items' => [],
                'room_types' => InventoryNeedAnalysisService::ROOM_TYPES,
            ]]);
        }

        $categories = InventoryCategory::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $items = InventoryItem::query()
            ->where('institution_id', $institutionId)
            ->whereNull('disposed_at')
            ->orderBy('name')
            ->get(['id', 'category_id', 'code', 'name', 'unit', 'tracking_type']);

        return response()->json(['data' => [
            'categories' => $categories,
            'items' => $items,
            'room_types' => InventoryNeedAnalysisService::ROOM_TYPES,
        ]]);
    }

    public function index(Request $request)
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            return $denied;
        }

        $institutionId = $this->resolveInstitutionId($request);
        if (! $institutionId) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }

        $query = InventoryNeedAnalysis::query()
            ->with('academicYear')
            ->where('institution_id', $institutionId);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('academic_year_label', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->get('per_page', 15), 100);

        return InventoryNeedAnalysisResource::collection(
            $query->orderByDesc('updated_at')->orderByDesc('id')->paginate($perPage)
        );
    }

    public function store(Request $request)
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            return $denied;
        }

        $institutionId = $this->resolveInstitutionId($request);
        if (! $institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 403);
        }

        $validated = $request->validate([
            'academic_year_id' => ['required', Rule::exists('academic_years', 'id')->whereNull('deleted_at')],
            'title' => 'nullable|string|max:200',
            'with_template' => 'sometimes|boolean',
            'source_analysis_id' => ['nullable', 'integer', Rule::exists('inventory_need_analysis', 'id')->whereNull('deleted_at')],
        ]);

        try {
            $analysis = $this->service->create($institutionId, [
                'academic_year_id' => (int) $validated['academic_year_id'],
                'title' => $validated['title'] ?? null,
                'with_template' => $request->has('with_template') ? $request->boolean('with_template') : true,
                'source_analysis_id' => $validated['source_analysis_id'] ?? null,
            ], $request->user()->id);

            return response()->json([
                'message' => 'Analisis kebutuhan dibuat',
                'data' => new InventoryNeedAnalysisResource($analysis),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create inventory need analysis', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function show(Request $request, InventoryNeedAnalysis $analysis)
    {
        if ($denied = $this->guard($request, $analysis)) {
            return $denied;
        }

        try {
            $analysis = $this->service->refreshCounts($analysis);

            return response()->json([
                'data' => new InventoryNeedAnalysisResource($analysis),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to show inventory need analysis', [
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function update(Request $request, InventoryNeedAnalysis $analysis)
    {
        if ($denied = $this->guard($request, $analysis)) {
            return $denied;
        }

        $validated = $request->validate([
            'academic_year_id' => ['sometimes', 'required', Rule::exists('academic_years', 'id')->whereNull('deleted_at')],
            'title' => 'sometimes|required|string|max:200',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $analysis = $this->service->update($analysis, $validated, $request->user()->id);

            return response()->json([
                'message' => 'Analisis kebutuhan diperbarui',
                'data' => new InventoryNeedAnalysisResource($analysis),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update inventory need analysis', [
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function destroy(Request $request, InventoryNeedAnalysis $analysis)
    {
        if ($denied = $this->guard($request, $analysis)) {
            return $denied;
        }

        $analysis->delete();

        return response()->json(['message' => 'Analisis kebutuhan dihapus']);
    }

    public function refresh(Request $request, InventoryNeedAnalysis $analysis)
    {
        if ($denied = $this->guard($request, $analysis)) {
            return $denied;
        }

        try {
            if ($analysis->status === 'final') {
                return response()->json(['message' => 'Dokumen final memakai angka yang sudah dikunci.'], 422);
            }
            $analysis->updated_by = $request->user()->id;
            $analysis = $this->service->refreshCounts($analysis);

            return response()->json([
                'message' => 'Angka diperbarui dari data terkini',
                'data' => new InventoryNeedAnalysisResource($analysis),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to refresh inventory need analysis', [
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function finalize(Request $request, InventoryNeedAnalysis $analysis)
    {
        if ($denied = $this->guard($request, $analysis)) {
            return $denied;
        }

        try {
            $analysis = $this->service->finalize($analysis, $request->user()->id);

            return response()->json([
                'message' => 'Dokumen difinalkan. Angka dikunci.',
                'data' => new InventoryNeedAnalysisResource($analysis),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to finalize inventory need analysis', [
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function reopen(Request $request, InventoryNeedAnalysis $analysis)
    {
        if ($denied = $this->guard($request, $analysis)) {
            return $denied;
        }

        try {
            $analysis = $this->service->reopen($analysis, $request->user()->id);

            return response()->json([
                'message' => 'Dokumen dibuka kembali dan angkanya diperbarui',
                'data' => new InventoryNeedAnalysisResource($analysis),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to reopen inventory need analysis', [
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function storeLine(Request $request, InventoryNeedAnalysis $analysis)
    {
        if ($denied = $this->guard($request, $analysis)) {
            return $denied;
        }

        $validated = $request->validate($this->lineRules());

        try {
            $analysis = $this->service->addLine($analysis, $validated, $request->user()->id);

            return response()->json([
                'message' => 'Baris ditambahkan',
                'data' => new InventoryNeedAnalysisResource($analysis),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to add inventory need line', [
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function updateLine(Request $request, InventoryNeedAnalysis $analysis, InventoryNeedLine $line)
    {
        if ($denied = $this->guard($request, $analysis)) {
            return $denied;
        }
        if ((int) $line->analysis_id !== (int) $analysis->id) {
            return response()->json(['message' => 'Baris tidak ditemukan'], 404);
        }

        $validated = $request->validate($this->lineRules());

        try {
            $analysis = $this->service->updateLine($analysis, $line, $validated, $request->user()->id);

            return response()->json([
                'message' => 'Baris diperbarui',
                'data' => new InventoryNeedAnalysisResource($analysis),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update inventory need line', [
                'analysis_id' => $analysis->id,
                'line_id' => $line->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function destroyLine(Request $request, InventoryNeedAnalysis $analysis, InventoryNeedLine $line)
    {
        if ($denied = $this->guard($request, $analysis)) {
            return $denied;
        }
        if ((int) $line->analysis_id !== (int) $analysis->id) {
            return response()->json(['message' => 'Baris tidak ditemukan'], 404);
        }

        try {
            $analysis = $this->service->deleteLine($analysis, $line, $request->user()->id);

            return response()->json([
                'message' => 'Baris dihapus',
                'data' => new InventoryNeedAnalysisResource($analysis),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to delete inventory need line', [
                'analysis_id' => $analysis->id,
                'line_id' => $line->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function exportPdf(Request $request, InventoryNeedAnalysis $analysis)
    {
        if ($denied = $this->guard($request, $analysis)) {
            return $denied;
        }

        try {
            $analysis = $this->service->refreshCounts($analysis);
            $institution = Institution::query()->find($analysis->institution_id);
            if (! $institution) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 404);
            }

            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');
            $asOf = $analysis->status === 'final' && $analysis->finalized_at
                ? $analysis->finalized_at
                : now();

            $pdf = DomPDF::loadView('inventory.need_analysis', [
                'institution' => $institution,
                'analysis' => $analysis,
                'printed_at' => $printedAt,
                'printed_by' => $request->user()->name,
                'as_of_date' => $asOf,
            ])->setPaper('a4', 'landscape');

            try {
                $pdf->render();
                $canvas = $pdf->getDomPDF()->getCanvas();
                $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans');
                $canvas->page_text(720, 575, 'Hal. {PAGE_NUM}/{PAGE_COUNT}', $font, 7, [0.35, 0.35, 0.35]);
            } catch (\Throwable $e) {
                Log::warning('Need analysis PDF page number skipped', ['error' => $e->getMessage()]);
            }

            $filename = 'Analisis_Kebutuhan_Sarpras_'.$analysis->id.'_'.now()->format('Ymd_His').'.pdf';

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            Log::error('Failed to export inventory need analysis', [
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Gagal mencetak analisis kebutuhan'], 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function lineRules(): array
    {
        $roomType = Rule::in(InventoryNeedAnalysisService::ROOM_TYPES);

        return [
            'section' => 'required|in:prasarana,sarana',
            'name' => 'required|string|max:150',
            'unit' => 'required|string|max:30',
            'basis_type' => 'required|in:siswa,rombel,ruang,manual',
            'basis_room_type' => ['nullable', 'required_if:basis_type,ruang', $roomType],
            'room_type' => ['nullable', 'required_if:section,prasarana', $roomType],
            'basis_value' => 'nullable|integer|min:0',
            'ratio_numerator' => 'required|integer|min:1',
            'ratio_denominator' => 'required|integer|min:1',
            'usable_policy' => 'required|in:baik_only,baik_dan_ringan',
            'category_id' => ['nullable', 'integer', Rule::exists('inventory_category', 'id')->whereNull('deleted_at')],
            'source_item_ids' => 'nullable|array|max:50',
            'source_item_ids.*' => ['integer', Rule::exists('inventory_item', 'id')->whereNull('deleted_at')],
            'priority' => 'required|in:tinggi,sedang,rendah',
            'unit_price' => 'nullable|numeric|min:0',
            'unit_repair_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    private function guard(Request $request, InventoryNeedAnalysis $analysis): ?\Illuminate\Http\JsonResponse
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            return $denied;
        }

        if (! InventoryAccess::canManage($request->user())) {
            return InventoryAccess::forbiddenManageResponse();
        }

        $institutionId = $this->resolveInstitutionId($request);
        if (! $institutionId || (int) $analysis->institution_id !== (int) $institutionId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return null;
    }
}

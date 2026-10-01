<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubjectCatalogRequest;
use App\Http\Requests\UpdateSubjectCatalogRequest;
use App\Http\Resources\SubjectCatalogResource;
use App\Models\SubjectCatalog;
use App\Services\SubjectCatalogService;
use Illuminate\Http\Request;

class SubjectCatalogController extends Controller
{
    public function __construct(
        protected SubjectCatalogService $subjectCatalogService
    ) {}

    /**
     * List katalog mapel global (semua user terautentikasi — untuk referensi/adopsi).
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'jenjang', 'active_only', 'per_page']);
        $perPage = min((int) $request->get('per_page', 15), 100);

        $items = $this->subjectCatalogService->list($filters, $perPage);

        return SubjectCatalogResource::collection($items);
    }

    /**
     * Meta opsi jenjang + aturan prefix kode.
     */
    public function meta(Request $request)
    {
        $jenjangOptions = [];
        foreach (SubjectCatalog::JENJANG_PREFIX as $key => $prefix) {
            $jenjangOptions[] = [
                'value' => $key,
                'label' => SubjectCatalog::JENJANG_LABELS[$key] ?? $key,
                'code_prefix' => $prefix,
                'code_hint' => $prefix . '001',
            ];
        }

        return response()->json([
            'data' => [
                'jenjang_options' => $jenjangOptions,
                'code_rules' => [
                    'format' => '4 digit angka',
                    'prefix' => SubjectCatalog::JENJANG_PREFIX,
                    'labels' => SubjectCatalog::JENJANG_LABELS,
                ],
            ],
        ]);
    }

    public function store(StoreSubjectCatalogRequest $request)
    {
        $item = $this->subjectCatalogService->create($request->validated());

        return response()->json([
            'message' => 'Mata pelajaran katalog berhasil ditambahkan',
            'data' => new SubjectCatalogResource($item),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $item = $this->subjectCatalogService->find((int) $id);

        return new SubjectCatalogResource($item);
    }

    public function update(UpdateSubjectCatalogRequest $request, $id)
    {
        $item = SubjectCatalog::findOrFail($id);
        $item = $this->subjectCatalogService->update($item, $request->validated());

        return response()->json([
            'message' => 'Mata pelajaran katalog berhasil diperbarui',
            'data' => new SubjectCatalogResource($item),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $item = SubjectCatalog::findOrFail($id);
        $this->subjectCatalogService->delete($item);

        return response()->json([
            'message' => 'Mata pelajaran katalog berhasil dihapus',
        ]);
    }
}

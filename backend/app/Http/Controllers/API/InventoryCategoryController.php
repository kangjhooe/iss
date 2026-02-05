<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryCategoryResource;
use App\Models\InventoryCategory;
use App\Repositories\InventoryCategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InventoryCategoryController extends Controller
{
    public function __construct(
        private InventoryCategoryRepository $repository
    ) {}

    /**
     * Display a listing of categories.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['is_active', 'search']);
            $institutionId = null;
            if ($request->user()->isSuperAdmin()) {
                $institutionId = $request->get('institution_id');
            } elseif ($request->user()->isAdmin()) {
                $institutionId = $request->get('institution_id') ?? $request->user()->institution_id;
            } else {
                $institutionId = $request->user()->institution_id;
            }
            if ($institutionId === null) {
                $perPage = min($request->get('per_page', 15), 100);
                return InventoryCategoryResource::collection(
                    new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage)
                );
            }

            $perPage = min($request->get('per_page', 15), 100);
            $categories = $this->repository->list($filters, $institutionId, $perPage);

            return InventoryCategoryResource::collection($categories);
        } catch (\Exception $e) {
            Log::error('Failed to list categories', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:10',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $institutionId = $request->user()->isAdminOrSuperAdmin() 
                ? $request->institution_id 
                : $request->user()->institution_id;

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            // Check if code already exists for this institution
            $exists = InventoryCategory::where('institution_id', $institutionId)
                ->where('code', $request->code)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Kode kategori sudah digunakan'], 422);
            }

            $data = $validator->validated();
            $data['institution_id'] = $institutionId;
            $data['is_active'] = $data['is_active'] ?? true;

            $category = $this->repository->create($data);
            $category->load('institution');

            return response()->json([
                'message' => 'Kategori berhasil ditambahkan',
                'data' => new InventoryCategoryResource($category),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create category', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Display the specified category.
     */
    public function show(Request $request, InventoryCategory $category)
    {
        try {
            if (!$request->user()->isAdminOrSuperAdmin() && (int) $category->institution_id !== (int) $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $category->load(['institution', 'items']);
            return new InventoryCategoryResource($category);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, InventoryCategory $category)
    {
        if (!$request->user()->isAdminOrSuperAdmin() && (int) $category->institution_id !== (int) $request->user()->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|max:10',
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Check code uniqueness if code is being updated
            if (isset($data['code']) && $data['code'] !== $category->code) {
                $exists = InventoryCategory::where('institution_id', $category->institution_id)
                    ->where('code', $data['code'])
                    ->where('id', '!=', $category->id)
                    ->exists();

                if ($exists) {
                    return response()->json(['message' => 'Kode kategori sudah digunakan'], 422);
                }
            }

            $category = $this->repository->update($category, $data);
            $category->load('institution');

            return response()->json([
                'message' => 'Kategori berhasil diperbarui',
                'data' => new InventoryCategoryResource($category),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update category', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Request $request, InventoryCategory $category)
    {
        try {
            if (!$request->user()->isAdminOrSuperAdmin() && (int) $category->institution_id !== (int) $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            // Check if category has items
            if ($category->items()->count() > 0) {
                return response()->json([
                    'message' => 'Kategori tidak dapat dihapus karena masih memiliki barang',
                ], 422);
            }

            $this->repository->delete($category);

            return response()->json(['message' => 'Kategori berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Failed to delete category', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstitutionRequest;
use App\Http\Requests\UpdateInstitutionRequest;
use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstitutionController extends Controller
{
    /**
     * Display a listing of institutions (admin only).
     */
    public function index(Request $request)
    {
        try {
            $query = Institution::query();

            // Only admin can see all institutions
            if (!$request->user()->isAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('npsn', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('level')) {
                $query->where('level', $request->level);
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
            $institutions = $query->select(['id', 'name', 'npsn', 'level', 'type', 'is_active', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return InstitutionResource::collection($institutions);
        } catch (\Exception $e) {
            Log::error('Failed to list institutions', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a newly created institution.
     */
    public function store(StoreInstitutionRequest $request)
    {
        try {
            $institution = Institution::create($request->validated());

            Log::info('Institution created', [
                'institution_id' => $institution->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Institusi berhasil dibuat',
                'data' => new InstitutionResource($institution),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create institution', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat membuat institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified institution.
     */
    public function show(Request $request, $id)
    {
        try {
            $institution = Institution::with(['users', 'students', 'teachers'])->findOrFail($id);

            // Jika bukan admin, hanya bisa melihat institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $institution->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return new InstitutionResource($institution);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Institusi tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get institution', [
                'institution_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update the specified institution.
     */
    public function update(UpdateInstitutionRequest $request, $id)
    {
        try {
            $institution = Institution::findOrFail($id);

            $institution->update($request->validated());

            Log::info('Institution updated', [
                'institution_id' => $institution->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Institusi berhasil diperbarui',
                'data' => new InstitutionResource($institution),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Institusi tidak ditemukan',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update institution', [
                'institution_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified institution.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $institution = Institution::findOrFail($id);

            // Hanya admin yang bisa menghapus
            if (!$request->user()->isAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $institution->delete();

            Log::info('Institution deleted', [
                'institution_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Institusi berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Institusi tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete institution', [
                'institution_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get current user's institution.
     */
    public function myInstitution(Request $request)
    {
        try {
            $institution = $request->user()->institution;

            if (!$institution) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 404);
            }

            return new InstitutionResource($institution);
        } catch (\Exception $e) {
            Log::error('Failed to get my institution', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

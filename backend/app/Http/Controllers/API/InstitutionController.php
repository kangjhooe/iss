<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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
            $institutions = $query->orderBy('created_at', 'desc')->paginate($perPage);

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
    public function store(Request $request)
    {
        try {
            // Only admin can create institutions
            if (!$request->user()->isAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'npsn' => 'nullable|string|size:8|regex:/^[0-9]{8}$/|unique:institution,npsn',
                'nss' => 'nullable|string|max:255',
                'level' => 'nullable|in:TK,SD,SMP,SMA,SMK,MA,MTs,MI,PAUD',
                'type' => 'required|in:Negeri,Swasta',
                'address' => 'nullable|string',
                'village' => 'nullable|string|max:255',
                'sub_district' => 'nullable|string|max:255',
                'district' => 'nullable|string|max:255',
                'province' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:10',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'website' => 'nullable|url|max:255',
                'principal_name' => 'nullable|string|max:255',
                'principal_nip' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'sometimes|boolean',
            ], [
                'name.required' => 'Nama institusi wajib diisi',
                'npsn.size' => 'NPSN harus terdiri dari 8 digit',
                'npsn.regex' => 'NPSN harus berupa angka 8 digit',
                'npsn.unique' => 'NPSN sudah terdaftar',
                'type.required' => 'Jenis institusi wajib diisi',
                'type.in' => 'Jenis institusi harus Negeri atau Swasta',
            ]);

            $institution = Institution::create($validated);

            Log::info('Institution created', [
                'institution_id' => $institution->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Institusi berhasil dibuat',
                'data' => new InstitutionResource($institution),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
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
            $institution = Institution::findOrFail($id);

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
    public function update(Request $request, $id)
    {
        try {
            $institution = Institution::findOrFail($id);

            // Jika bukan admin, hanya bisa update institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $institution->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'npsn' => 'nullable|string|size:8|regex:/^[0-9]{8}$/|unique:institution,npsn,' . $id,
                'nss' => 'nullable|string|max:255',
                'level' => 'nullable|in:TK,SD,SMP,SMA,SMK,MA,MTs,MI,PAUD',
                'type' => 'sometimes|required|in:Negeri,Swasta',
                'address' => 'nullable|string',
                'village' => 'nullable|string|max:255',
                'sub_district' => 'nullable|string|max:255',
                'district' => 'nullable|string|max:255',
                'province' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:10',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'website' => 'nullable|url|max:255',
                'principal_name' => 'nullable|string|max:255',
                'principal_nip' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'sometimes|boolean',
            ], [
                'npsn.size' => 'NPSN harus terdiri dari 8 digit',
                'npsn.regex' => 'NPSN harus berupa angka 8 digit',
                'npsn.unique' => 'NPSN sudah terdaftar',
            ]);

            $institution->update($validated);

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

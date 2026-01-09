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

            // Only admin or super admin can see all institutions
            $user = $request->user();
            if (!$user->isAdmin() && !$user->isSuperAdmin()) {
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
            
            // Eager load relationships if requested
            $with = [];
            if ($request->has('with')) {
                $with = explode(',', $request->get('with'));
                $allowedRelations = ['users', 'students', 'teachers', 'changeRequests'];
                $with = array_intersect($with, $allowedRelations);
            }
            
            $institutions = $query->select(['id', 'name', 'npsn', 'level', 'type', 'is_active', 'created_at'])
                ->when(!empty($with), function ($q) use ($with) {
                    return $q->with($with);
                })
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
            // Eager load relationships if requested
            $with = ['users', 'students', 'teachers'];
            if ($request->has('with')) {
                $requestedWith = explode(',', $request->get('with'));
                $allowedRelations = ['users', 'students', 'teachers', 'changeRequests'];
                $with = array_intersect($requestedWith, $allowedRelations);
                if (empty($with)) {
                    $with = ['users', 'students', 'teachers'];
                }
            }
            
            $institution = Institution::with($with)->findOrFail($id);

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
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 401);
            }
            
            $validated = $request->validated();
            
            // Check if user is trying to change name or npsn without super admin permission
            $isSuperAdmin = method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : false;
            
            if (!$isSuperAdmin) {
                if (isset($validated['name']) && $validated['name'] !== $institution->name) {
                    return response()->json([
                        'message' => 'Perubahan nama sekolah memerlukan persetujuan super admin. Silakan gunakan fitur request perubahan.',
                    ], 422);
                }
                
                if (isset($validated['npsn']) && $validated['npsn'] !== $institution->npsn) {
                    return response()->json([
                        'message' => 'Perubahan NPSN memerlukan persetujuan super admin. Silakan gunakan fitur request perubahan.',
                    ], 422);
                }
            }
            
            // Remove name and npsn from update if user is not super admin
            if (!$isSuperAdmin) {
                unset($validated['name']);
                unset($validated['npsn']);
            }

            $institution->update($validated);

            Log::info('Institution updated', [
                'institution_id' => $institution->id,
                'user_id' => $user->id,
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

            // Load active academic year and semester
            $institution->load(['activeAcademicYear', 'activeSemester']);

            return response()->json([
                'data' => new InstitutionResource($institution),
            ]);
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

    /**
     * Update active academic year and semester for institution.
     */
    public function updateActiveAcademicYear(Request $request, $id)
    {
        try {
            $institution = Institution::findOrFail($id);
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Only institution admin can update their own institution
            if (!$user->isAdmin() && $user->institution_id != $institution->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $request->validate([
                'active_academic_year_id' => 'nullable|exists:academic_years,id',
                'active_semester_id' => 'nullable|exists:semesters,id',
            ]);

            // Validate semester belongs to academic year
            if ($request->active_semester_id && $request->active_academic_year_id) {
                $semester = \App\Models\Semester::findOrFail($request->active_semester_id);
                if ($semester->academic_year_id != $request->active_academic_year_id) {
                    return response()->json([
                        'message' => 'Semester tidak sesuai dengan tahun ajaran yang dipilih',
                    ], 422);
                }
            }

            // If only semester is provided, validate it belongs to current academic year
            if ($request->active_semester_id && !$request->has('active_academic_year_id')) {
                $semester = \App\Models\Semester::findOrFail($request->active_semester_id);
                if ($institution->active_academic_year_id && $semester->academic_year_id != $institution->active_academic_year_id) {
                    return response()->json([
                        'message' => 'Semester tidak sesuai dengan tahun ajaran aktif saat ini',
                    ], 422);
                }
            }

            $institution->update([
                'active_academic_year_id' => $request->active_academic_year_id ?? $institution->active_academic_year_id,
                'active_semester_id' => $request->active_semester_id ?? $institution->active_semester_id,
            ]);

            $institution->load(['activeAcademicYear', 'activeSemester']);

            Log::info('Institution active academic year updated', [
                'institution_id' => $institution->id,
                'user_id' => $user->id,
                'academic_year_id' => $institution->active_academic_year_id,
                'semester_id' => $institution->active_semester_id,
            ]);

            return response()->json([
                'message' => 'Tahun ajaran dan semester aktif berhasil diperbarui',
                'data' => new InstitutionResource($institution),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Institusi tidak ditemukan',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update active academic year', [
                'institution_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui tahun ajaran aktif',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

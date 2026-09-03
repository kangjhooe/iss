<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeacherController extends Controller
{
    /**
     * Display a listing of teachers.
     */
    public function index(Request $request)
    {
        try {
            $query = Teacher::query();
            $user = $request->user();
            $institutionId = null;

            if (filter_var($request->get('only_trashed'), FILTER_VALIDATE_BOOLEAN)) {
                $query->onlyTrashed();
            } elseif (filter_var($request->get('with_trashed'), FILTER_VALIDATE_BOOLEAN)) {
                $query->withTrashed();
            }

            // Filter berdasarkan institusi aktif (induk / non-induk) — fail-closed
            if (!$user->isAdminOrSuperAdmin()) {
                $institutionId = InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));
                if (!$institutionId) {
                    return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
                }
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            if ($institutionId) {
                $query->where(function ($q) use ($institutionId) {
                    $q->where('institution_id', $institutionId)
                        ->orWhereHas('assignments', function ($assignmentQuery) use ($institutionId) {
                            $assignmentQuery->where('institution_id', $institutionId)
                                ->where('status', 'approved');
                        });
                });
            } elseif (!$user->isAdminOrSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('nik', 'like', '%' . $search . '%')
                      ->orWhere('nip', 'like', '%' . $search . '%')
                      ->orWhere('nuptk', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('employment_status')) {
                $query->where('employment_status', $request->employment_status);
            }

            $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
            $relations = ['institution:id,name'];
            if ($institutionId) {
                $relations['assignments'] = function ($assignmentQuery) use ($institutionId) {
                    $assignmentQuery->where('institution_id', $institutionId)
                        ->where('status', 'approved');
                };
            }

            $teachers = $query->select(['id', 'institution_id', 'nik', 'type', 'nip', 'nuptk', 'name', 'gender', 'subject', 'status', 'employment_status', 'notes', 'photo_path', 'created_at'])
                ->with($relations)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            if ($institutionId) {
                $request->attributes->set('current_institution_id', $institutionId);
            }

            return TeacherResource::collection($teachers);
        } catch (\Exception $e) {
            Log::error('Failed to list teachers', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data guru',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a newly created teacher.
     */
    public function store(StoreTeacherRequest $request)
    {
        try {
            $institutionId = InstitutionContext::resolveForUser(
                $request->user(),
                $request,
                $request->get('institution_id')
            );

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $validated = $request->validated();
            $validated['institution_id'] = $institutionId;
            $validated['type'] = 'Guru'; // Ensure type is set to Guru

            $teacher = Teacher::create($validated);

            Log::info('Teacher created', [
                'teacher_id' => $teacher->id,
                'institution_id' => $institutionId,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Guru berhasil ditambahkan',
                'data' => new TeacherResource($teacher->load('institution')),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create teacher', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menambahkan guru',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified teacher.
     */
    public function show(Request $request, $id)
    {
        try {
            $teacher = Teacher::findOrFail($id);
            $user = $request->user();
            $currentInstitutionId = \App\Support\InstitutionContext::resolveForUser(
                $user,
                $request,
                $request->get('institution_id')
            );

            // Jika bukan admin/super admin, hanya bisa melihat guru dari institusi sendiri
            if (!$user->isAdminOrSuperAdmin() && $currentInstitutionId != $teacher->institution_id) {
                $hasApprovedAssignment = $teacher->assignments()
                    ->where('institution_id', $currentInstitutionId)
                    ->where('status', 'approved')
                    ->exists();

                if (!$hasApprovedAssignment) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            $relations = ['institution'];

            if ($user->isAdminOrSuperAdmin() || $teacher->institution_id == $currentInstitutionId) {
                $relations[] = 'assignments.institution';
                $relations[] = 'assignments.requester';
                $relations[] = 'assignments.approver';
            } else {
                $relations['assignments'] = function ($assignmentQuery) use ($currentInstitutionId) {
                    $assignmentQuery->where('institution_id', $currentInstitutionId)
                        ->where('status', 'approved')
                        ->with('institution:id,name');
                };
            }

            $teacher->load($relations);

            if ($currentInstitutionId) {
                $request->attributes->set('current_institution_id', $currentInstitutionId);
            }

            return new TeacherResource($teacher);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Guru tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get teacher', [
                'teacher_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data guru',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update the specified teacher.
     */
    public function update(UpdateTeacherRequest $request, $id)
    {
        try {
            $teacher = Teacher::findOrFail($id);

            // Jika bukan admin/super admin, hanya bisa update guru dari institusi yang boleh diakses
            if (!$request->user()->isAdminOrSuperAdmin()
                && !InstitutionContext::canAccessInstitution($request->user(), (int) $teacher->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $teacher->update($request->validated());

            Log::info('Teacher updated', [
                'teacher_id' => $teacher->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Guru berhasil diperbarui',
                'data' => new TeacherResource($teacher->load('institution')),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Guru tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update teacher', [
                'teacher_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui guru',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified teacher.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $teacher = Teacher::findOrFail($id);

            // Jika bukan admin/super admin, hanya bisa hapus guru dari institusi yang boleh diakses
            if (!$request->user()->isAdminOrSuperAdmin()
                && !InstitutionContext::canAccessInstitution($request->user(), (int) $teacher->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $teacher->delete();

            Log::info('Teacher deleted', [
                'teacher_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Guru berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Guru tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete teacher', [
                'teacher_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus guru',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted teacher.
     */
    public function restore(Request $request, $id)
    {
        try {
            $teacher = Teacher::withTrashed()->findOrFail($id);

            if (!$request->user()->isAdminOrSuperAdmin()
                && !InstitutionContext::canAccessInstitution($request->user(), (int) $teacher->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($teacher->trashed()) {
                $teacher->restore();
            }

            return response()->json([
                'message' => 'Guru berhasil dipulihkan',
                'data' => new TeacherResource($teacher->fresh(['institution'])),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Guru tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to restore teacher', [
                'teacher_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memulihkan guru',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

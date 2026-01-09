<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
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

            // Filter berdasarkan institusi user yang login
            if (!$request->user()->isAdmin()) {
                $query->where('institution_id', $request->user()->institution_id);
            } elseif ($request->has('institution_id')) {
                $query->where('institution_id', $request->institution_id);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
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
            $teachers = $query->select(['id', 'institution_id', 'nip', 'nuptk', 'name', 'gender', 'status', 'employment_status', 'created_at'])
                ->with('institution:id,name')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

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
            $institutionId = $request->user()->isAdmin() 
                ? $request->institution_id 
                : $request->user()->institution_id;

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $validated = $request->validated();
            $validated['institution_id'] = $institutionId;

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
            $teacher = Teacher::with('institution')->findOrFail($id);

            // Jika bukan admin, hanya bisa melihat guru dari institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $teacher->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
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

            // Jika bukan admin, hanya bisa update guru dari institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $teacher->institution_id) {
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

            // Jika bukan admin, hanya bisa hapus guru dari institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $teacher->institution_id) {
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
}

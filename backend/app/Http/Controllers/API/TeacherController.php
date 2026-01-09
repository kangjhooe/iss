<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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
            $teachers = $query->with('institution')->orderBy('created_at', 'desc')->paginate($perPage);

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
    public function store(Request $request)
    {
        try {
            $institutionId = $request->user()->isAdmin() 
                ? $request->institution_id 
                : $request->user()->institution_id;

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $validated = $request->validate([
            'institution_id' => 'sometimes|exists:institution,id',
            'nip' => 'nullable|string',
            'nuptk' => 'nullable|string|unique:teacher,nuptk',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'religion' => 'nullable|string',
            'employment_status' => 'nullable|in:PNS,CPNS,Guru Tetap Yayasan,Guru Honor Sekolah,Guru Kontrak',
            'education_level' => 'nullable|in:SMA,D3,S1,S2,S3',
            'major' => 'nullable|string',
            'subject' => 'nullable|string',
            'status' => 'nullable|in:Aktif,Pensiun,Pindah,Tidak Aktif',
            'join_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

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
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
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
    public function update(Request $request, $id)
    {
        try {
            $teacher = Teacher::findOrFail($id);

            // Jika bukan admin, hanya bisa update guru dari institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $teacher->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
            'nip' => 'nullable|string',
            'nuptk' => 'nullable|string|unique:teacher,nuptk,' . $id,
            'name' => 'sometimes|required|string|max:255',
            'gender' => 'sometimes|required|in:L,P',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'religion' => 'nullable|string',
            'employment_status' => 'nullable|in:PNS,CPNS,Guru Tetap Yayasan,Guru Honor Sekolah,Guru Kontrak',
            'education_level' => 'nullable|in:SMA,D3,S1,S2,S3',
            'major' => 'nullable|string',
            'subject' => 'nullable|string',
            'status' => 'nullable|in:Aktif,Pensiun,Pindah,Tidak Aktif',
            'join_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

            $teacher->update($validated);

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
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
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

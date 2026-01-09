<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    /**
     * Display a listing of students.
     */
    public function index(Request $request)
    {
        try {
            $query = Student::query();

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
                      ->orWhere('nis', 'like', '%' . $search . '%')
                      ->orWhere('nisn', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('class')) {
                $query->where('class', $request->class);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
            $students = $query->with('institution')->orderBy('created_at', 'desc')->paginate($perPage);

            return StudentResource::collection($students);
        } catch (\Exception $e) {
            Log::error('Failed to list students', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a newly created student.
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
            'nis' => 'nullable|string',
            'nisn' => 'nullable|string|unique:student,nisn',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'religion' => 'nullable|string',
            'class' => 'nullable|string',
            'academic_year' => 'nullable|string',
            'status' => 'nullable|in:Aktif,Lulus,Pindah,Drop Out,Tidak Aktif',
            'father_name' => 'nullable|string',
            'mother_name' => 'nullable|string',
            'guardian_name' => 'nullable|string',
            'guardian_phone' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['institution_id'] = $institutionId;

            $student = Student::create($validated);

            Log::info('Student created', [
                'student_id' => $student->id,
                'institution_id' => $institutionId,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Siswa berhasil ditambahkan',
                'data' => new StudentResource($student->load('institution')),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create student', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menambahkan siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified student.
     */
    public function show(Request $request, $id)
    {
        try {
            $student = Student::with('institution')->findOrFail($id);

            // Jika bukan admin, hanya bisa melihat siswa dari institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $student->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return new StudentResource($student);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get student', [
                'student_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update the specified student.
     */
    public function update(Request $request, $id)
    {
        try {
            $student = Student::findOrFail($id);

            // Jika bukan admin, hanya bisa update siswa dari institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $student->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
            'nis' => 'nullable|string',
            'nisn' => 'nullable|string|unique:student,nisn,' . $id,
            'name' => 'sometimes|required|string|max:255',
            'gender' => 'sometimes|required|in:L,P',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'religion' => 'nullable|string',
            'class' => 'nullable|string',
            'academic_year' => 'nullable|string',
            'status' => 'nullable|in:Aktif,Lulus,Pindah,Drop Out,Tidak Aktif',
            'father_name' => 'nullable|string',
            'mother_name' => 'nullable|string',
            'guardian_name' => 'nullable|string',
            'guardian_phone' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

            $student->update($validated);

            Log::info('Student updated', [
                'student_id' => $student->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Siswa berhasil diperbarui',
                'data' => new StudentResource($student->load('institution')),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update student', [
                'student_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified student.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $student = Student::findOrFail($id);

            // Jika bukan admin, hanya bisa hapus siswa dari institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $student->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $student->delete();

            Log::info('Student deleted', [
                'student_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Siswa berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete student', [
                'student_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

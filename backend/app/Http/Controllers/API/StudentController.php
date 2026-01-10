<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
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
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $query->where('institution_id', $request->user()->institution_id);
            } elseif ($request->has('institution_id')) {
                $query->where('institution_id', $request->institution_id);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('nik', 'like', '%' . $search . '%')
                      ->orWhere('nis', 'like', '%' . $search . '%')
                      ->orWhere('nisn', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('class')) {
                $query->where('class', $request->class);
            }

            if ($request->has('academic_year')) {
                $query->where('academic_year', $request->academic_year);
            }

            if ($request->has('academic_year_id')) {
                $query->where('academic_year_id', $request->academic_year_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
            $students = $query->select(['id', 'institution_id', 'nik', 'nis', 'nisn', 'name', 'gender', 'class', 'status', 'created_at'])
                ->with('institution:id,name')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

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
    public function store(StoreStudentRequest $request)
    {
        try {
            $institutionId = $request->user()->isAdminOrSuperAdmin() 
                ? $request->institution_id 
                : $request->user()->institution_id;

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $validated = $request->validated();
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

            // Jika bukan admin/super admin, hanya bisa melihat siswa dari institusi sendiri
            if (!$request->user()->isAdminOrSuperAdmin() && $request->user()->institution_id != $student->institution_id) {
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
    public function update(UpdateStudentRequest $request, $id)
    {
        try {
            $student = Student::findOrFail($id);

            // Jika bukan admin/super admin, hanya bisa update siswa dari institusi sendiri
            if (!$request->user()->isAdminOrSuperAdmin() && $request->user()->institution_id != $student->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $student->update($request->validated());

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

            // Jika bukan admin/super admin, hanya bisa hapus siswa dari institusi sendiri
            if (!$request->user()->isAdminOrSuperAdmin() && $request->user()->institution_id != $student->institution_id) {
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

    /**
     * Import students from Excel data.
     */
    public function import(Request $request)
    {
        try {
            $studentsData = $request->input('students', []);
            
            if (empty($studentsData) || !is_array($studentsData)) {
                return response()->json([
                    'message' => 'Data siswa tidak valid',
                ], 400);
            }

            $institutionId = $request->user()->isAdmin() 
                ? $request->input('institution_id')
                : $request->user()->institution_id;

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($studentsData as $index => $studentData) {
                try {
                    // Validasi data minimal
                    if (empty($studentData['nik']) || empty($studentData['name'])) {
                        $errors[] = "Baris " . ($index + 1) . ": NIK dan Nama Lengkap wajib diisi";
                        $errorCount++;
                        continue;
                    }

                    // Cek apakah siswa sudah ada berdasarkan NIK
                    $existingStudent = Student::where('institution_id', $institutionId)
                        ->where('nik', $studentData['nik'])
                        ->first();

                    if ($existingStudent) {
                        // Update jika sudah ada
                        $existingStudent->update(array_merge($studentData, [
                            'institution_id' => $institutionId
                        ]));
                        $successCount++;
                    } else {
                        // Create jika belum ada
                        Student::create(array_merge($studentData, [
                            'institution_id' => $institutionId
                        ]));
                        $successCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($index + 1) . ": " . $e->getMessage();
                    $errorCount++;
                    Log::error('Failed to import student', [
                        'row' => $index + 1,
                        'error' => $e->getMessage(),
                        'data' => $studentData,
                    ]);
                }
            }

            Log::info('Students imported', [
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'institution_id' => $institutionId,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Import selesai',
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to import students', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengimpor data siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

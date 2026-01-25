<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClassRequest;
use App\Http\Requests\UpdateClassRequest;
use App\Http\Resources\ClassResource;
use App\Http\Resources\StudentResource;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ClassService;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function __construct(
        protected ClassService $classService
    ) {}

    /**
     * Display a listing of classes.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'grade', 'academic_year', 'academic_year_id', 'semester_id', 'status', 'room_id', 'teacher_id']);
        
        $institutionId = null;
        if (!$request->user()->isAdminOrSuperAdmin()) {
            $institutionId = $request->user()->institution_id;
        } elseif ($request->has('institution_id')) {
            $institutionId = $request->get('institution_id');
        }

        // Jika tidak ada filter academic_year_id, gunakan active_academic_year_id dari institusi
        if (!isset($filters['academic_year_id']) && $institutionId) {
            $institution = Institution::find($institutionId);
            if ($institution && $institution->active_academic_year_id) {
                $filters['academic_year_id'] = $institution->active_academic_year_id;
            }
        }

        // Jika tidak ada filter semester_id, gunakan active_semester_id dari institusi
        if (!isset($filters['semester_id']) && $institutionId) {
            $institution = Institution::find($institutionId);
            if ($institution && $institution->active_semester_id) {
                $filters['semester_id'] = $institution->active_semester_id;
            }
        }

        $perPage = min($request->get('per_page', 15), 100);
        $classes = $this->classService->list($filters, $institutionId, $perPage);

        return ClassResource::collection($classes);
    }

    /**
     * Store a newly created class.
     */
    public function store(StoreClassRequest $request)
    {
        $institutionId = $request->user()->isAdminOrSuperAdmin() 
            ? $request->institution_id 
            : $request->user()->institution_id;

        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        // Get institution with active academic year and semester
        $institution = Institution::with(['activeAcademicYear', 'activeSemester'])->findOrFail($institutionId);
        
        if (!$institution->active_academic_year_id) {
            return response()->json([
                'message' => 'Tahun ajaran aktif belum ditetapkan untuk institusi ini'
            ], 400);
        }

        if (!$institution->active_semester_id) {
            return response()->json([
                'message' => 'Semester aktif belum ditetapkan untuk institusi ini'
            ], 400);
        }

        // Get academic year data
        $academicYear = $institution->activeAcademicYear;
        if (!$academicYear) {
            return response()->json([
                'message' => 'Data tahun ajaran aktif tidak ditemukan'
            ], 400);
        }

        $validated = $request->validated();
        $validated['institution_id'] = $institutionId;
        $validated['academic_year_id'] = $institution->active_academic_year_id; // Set otomatis dari tahun ajaran aktif
        $validated['semester_id'] = $validated['semester_id'] ?? $institution->active_semester_id; // Set otomatis dari semester aktif jika tidak ada
        $validated['academic_year'] = $academicYear->code; // Set academic_year string dari code
        $validated['status'] = $validated['status'] ?? 'Aktif';

        $class = $this->classService->create($validated);

        return response()->json([
            'message' => 'Kelas berhasil ditambahkan',
            'data' => new ClassResource($class->load(['institution', 'room', 'teacher', 'academicYear', 'semester'])),
        ], 201);
    }

    /**
     * Display the specified class.
     */
    public function show(Request $request, $id)
    {
        $class = $this->classService->find($id);

        // Jika bukan admin/super admin, hanya bisa melihat kelas dari institusi sendiri
        if (!$request->user()->isAdminOrSuperAdmin() && $request->user()->institution_id != $class->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new ClassResource($class);
    }

    /**
     * Update the specified class.
     */
    public function update(UpdateClassRequest $request, $id)
    {
        $class = SchoolClass::findOrFail($id);

        // Jika bukan admin, hanya bisa update kelas dari institusi sendiri
        if (!$request->user()->isAdmin() && $request->user()->institution_id != $class->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();
        // Academic year tidak bisa diubah setelah kelas dibuat
        unset($validated['academic_year_id']);

        $class = $this->classService->update($class, $validated);

        return response()->json([
            'message' => 'Kelas berhasil diperbarui',
            'data' => new ClassResource($class->load(['institution', 'room', 'teacher', 'academicYear'])),
        ]);
    }

    /**
     * Remove the specified class.
     */
    public function destroy(Request $request, $id)
    {
        $class = SchoolClass::findOrFail($id);

        // Jika bukan admin, hanya bisa hapus kelas dari institusi sendiri
        if (!$request->user()->isAdmin() && $request->user()->institution_id != $class->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if class has students
        if ($class->students()->count() > 0) {
            return response()->json([
                'message' => 'Tidak dapat menghapus kelas yang masih memiliki siswa. Pindahkan siswa terlebih dahulu.',
            ], 400);
        }

        $this->classService->delete($class);

        return response()->json([
            'message' => 'Kelas berhasil dihapus',
        ]);
    }

    /**
     * Add students to class.
     */
    public function addStudents(Request $request, $id)
    {
        $class = SchoolClass::findOrFail($id);

        // Jika bukan admin/super admin, hanya bisa menambah siswa ke kelas dari institusi sendiri
        if (!$request->user()->isAdminOrSuperAdmin() && $request->user()->institution_id != $class->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'required|exists:student,id',
        ]);

        $studentIds = $request->student_ids;
        $addedCount = 0;
        $errors = [];

        foreach ($studentIds as $studentId) {
            try {
                $student = Student::findOrFail($studentId);

                // Check if student belongs to same institution
                if ($student->institution_id != $class->institution_id) {
                    $errors[] = "Siswa {$student->name} tidak dapat ditambahkan karena berbeda institusi";
                    continue;
                }

                // Check if student is already in another class
                if ($student->class_id && $student->class_id != $class->id) {
                    $errors[] = "Siswa {$student->name} sudah berada di kelas lain";
                    continue;
                }

                // Check if class has capacity
                if ($class->capacity && $class->students()->count() >= $class->capacity) {
                    $errors[] = "Kelas sudah penuh (kapasitas: {$class->capacity})";
                    break;
                }

                // Update student class_id, class (string), academic_year_id, and academic_year
                $student->update([
                    'class_id' => $class->id,
                    'class' => $class->name, // Update class string field
                    'academic_year_id' => $class->academic_year_id,
                    'academic_year' => $class->academic_year,
                ]);

                $addedCount++;
            } catch (\Exception $e) {
                $errors[] = "Gagal menambahkan siswa ID {$studentId}: " . $e->getMessage();
            }
        }

        $message = "Berhasil menambahkan {$addedCount} siswa ke kelas";
        if (count($errors) > 0) {
            $message .= ". " . implode(', ', $errors);
        }

        return response()->json([
            'message' => $message,
            'added_count' => $addedCount,
            'errors' => $errors,
            'data' => new ClassResource($class->fresh(['institution', 'room', 'teacher', 'academicYear'])),
        ]);
    }

    /**
     * Get available students for class (students without class or from same academic year).
     */
    public function getAvailableStudents(Request $request, $id)
    {
        $class = SchoolClass::findOrFail($id);

        // Jika bukan admin/super admin, hanya bisa melihat siswa dari institusi sendiri
        if (!$request->user()->isAdminOrSuperAdmin() && $request->user()->institution_id != $class->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Student::where('institution_id', $class->institution_id)
            ->where('status', 'Aktif')
            ->whereNull('class_id'); // Only students without class

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('nis', 'like', '%' . $search . '%')
                  ->orWhere('nisn', 'like', '%' . $search . '%');
            });
        }

        // Exclude students already in this class
        $query->whereDoesntHave('class', function($q) use ($class) {
            $q->where('id', $class->id);
        });

        $perPage = min($request->get('per_page', 50), 100);
        $students = $query->orderBy('name', 'asc')->paginate($perPage);

        return StudentResource::collection($students);
    }

    /**
     * Remove student from class.
     */
    public function removeStudent(Request $request, $id, $studentId)
    {
        $class = SchoolClass::findOrFail($id);

        // Jika bukan admin/super admin, hanya bisa menghapus siswa dari kelas dari institusi sendiri
        if (!$request->user()->isAdminOrSuperAdmin() && $request->user()->institution_id != $class->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $student = Student::findOrFail($studentId);

        // Check if student belongs to same institution
        if ($student->institution_id != $class->institution_id) {
            return response()->json([
                'message' => 'Siswa tidak dapat dihapus karena berbeda institusi'
            ], 400);
        }

        // Check if student is in this class
        if ($student->class_id != $class->id) {
            return response()->json([
                'message' => 'Siswa tidak berada di kelas ini'
            ], 400);
        }

        // Remove student from class
        $student->update([
            'class_id' => null,
            'class' => null,
        ]);

        return response()->json([
            'message' => 'Siswa berhasil dihapus dari kelas',
            'data' => new ClassResource($class->fresh(['institution', 'room', 'teacher', 'academicYear'])),
        ]);
    }

    /**
     * Get students in class.
     */
    public function getStudents(Request $request, $id)
    {
        $class = SchoolClass::findOrFail($id);

        // Jika bukan admin/super admin, hanya bisa melihat siswa dari kelas dari institusi sendiri
        if (!$request->user()->isAdminOrSuperAdmin() && $request->user()->institution_id != $class->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = min($request->get('per_page', 50), 100);
        $students = $class->students()
            ->orderBy('name', 'asc')
            ->paginate($perPage);

        return StudentResource::collection($students);
    }
}

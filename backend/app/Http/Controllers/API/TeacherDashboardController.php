<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassResource;
use App\Http\Resources\EmployeeResource;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeacherDashboardController extends Controller
{
    /**
     * Get teacher dashboard summary.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user || !$user->isTeacher()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $user->load('teacherProfile');
            $teacher = $user->teacherProfile;

            if (!$teacher) {
                return response()->json(['message' => 'Profil guru tidak ditemukan'], 404);
            }

            $institutionId = $teacher->institution_id ?? $user->institution_id;
            $activeAcademicYear = null;

            if ($institutionId) {
                $institution = Institution::find($institutionId);
                if ($institution && $institution->active_academic_year_id) {
                    $activeAcademicYear = AcademicYear::find($institution->active_academic_year_id);
                }
            }

            $classesQuery = SchoolClass::withCount('students')
                ->with([
                    'room:id,name,code',
                    'academicYear:id,name,code',
                ])
                ->where('teacher_id', $teacher->id);

            if ($institutionId) {
                $classesQuery->where('institution_id', $institutionId);
            }

            if ($activeAcademicYear) {
                $classesQuery->where('academic_year_id', $activeAcademicYear->id);
            }

            $classes = $classesQuery
                ->orderBy('grade')
                ->orderBy('name')
                ->get();

            $totalStudents = $classes->sum('students_count');

            return response()->json([
                'data' => [
                    'teacher' => new EmployeeResource($teacher->load('institution')),
                    'summary' => [
                        'total_classes' => $classes->count(),
                        'total_students' => $totalStudents,
                    ],
                    'classes' => ClassResource::collection($classes),
                    'active_academic_year' => $activeAcademicYear ? [
                        'id' => $activeAcademicYear->id,
                        'name' => $activeAcademicYear->name,
                        'code' => $activeAcademicYear->code,
                    ] : null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load teacher dashboard', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memuat dashboard guru',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

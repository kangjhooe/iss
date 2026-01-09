<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Land;
use App\Models\Building;
use App\Models\Room;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get comprehensive statistics for an institution
     */
    public function getStatistics(Request $request, $institutionId = null)
    {
        try {
            $user = $request->user();
            
            // Determine institution ID
            if ($institutionId) {
                // Super admin or admin can access any institution
                if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
                $targetInstitutionId = $institutionId;
            } else {
                // Use user's institution
                if ($user->isSuperAdmin()) {
                    return response()->json(['message' => 'Institution ID required for super admin'], 400);
                }
                $targetInstitutionId = $user->institution_id;
                
                // Check if user has institution
                if (!$targetInstitutionId) {
                    return response()->json(['message' => 'User tidak memiliki institusi'], 400);
                }
            }

            // Get institution
            $institution = Institution::find($targetInstitutionId);
            
            if (!$institution) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 404);
            }
            
            // Get active academic year
            $activeAcademicYearId = $institution->active_academic_year_id;
            $academicYear = null;
            if ($activeAcademicYearId) {
                $academicYear = AcademicYear::find($activeAcademicYearId);
            }

            // Get filter parameters
            $month = $request->get('month');
            $year = $request->get('year');
            $compareWithPrevious = $request->get('compare_with_previous', false);

            // Get institution data
            $institutionData = [
                'id' => $institution->id,
                'name' => $institution->name,
                'npsn' => $institution->npsn,
                'nss' => $institution->nss,
                'level' => $institution->level,
                'type' => $institution->type,
                'address' => $institution->address,
                'village' => $institution->village,
                'sub_district' => $institution->sub_district,
                'district' => $institution->district,
                'province' => $institution->province,
                'postal_code' => $institution->postal_code,
                'phone' => $institution->phone,
                'email' => $institution->email,
                'website' => $institution->website,
                'principal_name' => $institution->principal_name,
                'principal_nip' => $institution->principal_nip,
            ];

            // Get students statistics by grade (7, 8, 9)
            $studentsByGrade = $this->getStudentsByGrade($targetInstitutionId, $activeAcademicYearId, $month, $year);
            
            // Get teachers statistics
            $teachersStats = $this->getTeachersStatistics($targetInstitutionId);
            
            // Get facilities statistics
            $facilitiesStats = $this->getFacilitiesStatistics($targetInstitutionId);
            
            // Get classes statistics
            $classesStats = $this->getClassesStatistics($targetInstitutionId, $activeAcademicYearId);
            
            // Get comparison data if requested
            $comparisonData = null;
            if ($compareWithPrevious && $academicYear) {
                $previousAcademicYear = AcademicYear::where('id', '!=', $activeAcademicYearId)
                    ->where('end_date', '<', $academicYear->start_date)
                    ->orderBy('end_date', 'desc')
                    ->first();
                
                if ($previousAcademicYear) {
                    $comparisonData = [
                        'academic_year' => $previousAcademicYear->name,
                        'students' => $this->getStudentsByGrade($targetInstitutionId, $previousAcademicYear->id, null, null),
                        'teachers' => $this->getTeachersStatistics($targetInstitutionId),
                        'classes' => $this->getClassesStatistics($targetInstitutionId, $previousAcademicYear->id),
                    ];
                }
            }

            // Calculate summary statistics
            $totalStudents = array_sum(array_column($studentsByGrade, 'total'));
            $totalTeachers = $teachersStats['total'];
            $totalClasses = array_sum(array_column($classesStats, 'count'));
            
            $summary = [
                'total_students' => $totalStudents,
                'total_teachers' => $totalTeachers,
                'total_classes' => $totalClasses,
                'total_facilities' => $facilitiesStats['total'],
                'student_teacher_ratio' => $totalTeachers > 0 ? round($totalStudents / $totalTeachers, 2) : 0,
                'average_students_per_class' => $totalClasses > 0 ? round($totalStudents / $totalClasses, 2) : 0,
            ];

            return response()->json([
                'data' => [
                    'institution' => $institutionData,
                    'academic_year' => $academicYear ? [
                        'id' => $academicYear->id,
                        'name' => $academicYear->name,
                        'code' => $academicYear->code,
                    ] : null,
                    'summary' => $summary,
                    'students' => $studentsByGrade,
                    'teachers' => $teachersStats,
                    'classes' => $classesStats,
                    'facilities' => $facilitiesStats,
                    'comparison' => $comparisonData,
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                    'period' => [
                        'month' => $month,
                        'year' => $year,
                    ],
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Institution not found in report', [
                'institution_id' => $targetInstitutionId ?? null,
                'user_id' => $user->id ?? null,
            ]);
            return response()->json(['message' => 'Institusi tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get report statistics', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
                'institution_id' => $targetInstitutionId ?? null,
            ]);
            return response()->json([
                'message' => 'Gagal mengambil data laporan',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get students statistics by grade
     */
    private function getStudentsByGrade($institutionId, $academicYearId = null, $month = null, $year = null)
    {
        $query = Student::where('institution_id', $institutionId)
            ->where('status', 'Aktif');

        // Filter by academic year if provided
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        // Filter by month/year if provided (filter by created_at or updated_at)
        if ($month && $year) {
            $query->whereYear('created_at', $year)
                ->whereMonth('created_at', $month);
        }

        // Get students with class relationship
        // Load the relationship to SchoolClass via class_id
        $students = $query->with(['class' => function($q) {
            $q->select('id', 'grade', 'name');
        }])->get();
        
        $result = [
            'grade_7' => ['male' => 0, 'female' => 0, 'total' => 0],
            'grade_8' => ['male' => 0, 'female' => 0, 'total' => 0],
            'grade_9' => ['male' => 0, 'female' => 0, 'total' => 0],
        ];

        foreach ($students as $student) {
            $grade = null;
            
            // Try to get grade from class relationship (SchoolClass model via class_id)
            // The relationship method is 'class()' which returns SchoolClass
            $schoolClass = $student->class; // This calls the relationship
            
            if ($schoolClass && is_object($schoolClass) && property_exists($schoolClass, 'grade') && $schoolClass->grade !== null) {
                $grade = (int)$schoolClass->grade;
            } else {
                // Fallback: try to extract from class field (string field in student table)
                // Access the raw attribute to avoid relationship conflict
                try {
                    $classStr = $student->getAttributes()['class'] ?? '';
                    if (preg_match('/\b([789])\b/', $classStr, $matches)) {
                        $grade = (int)$matches[1];
                    }
                } catch (\Exception $e) {
                    // If class field doesn't exist, skip this student
                    continue;
                }
            }

            if ($grade >= 7 && $grade <= 9) {
                $gradeKey = 'grade_' . $grade;
                if (isset($result[$gradeKey])) {
                    if ($student->gender === 'L') {
                        $result[$gradeKey]['male']++;
                    } else {
                        $result[$gradeKey]['female']++;
                    }
                    $result[$gradeKey]['total']++;
                }
            }
        }

        return $result;
    }

    /**
     * Get teachers statistics
     */
    private function getTeachersStatistics($institutionId)
    {
        $teachers = Teacher::where('institution_id', $institutionId)
            ->where('status', 'Aktif')
            ->get();

        $male = $teachers->where('gender', 'L')->count();
        $female = $teachers->where('gender', 'P')->count();

        return [
            'total' => $teachers->count(),
            'male' => $male,
            'female' => $female,
        ];
    }

    /**
     * Get classes statistics
     */
    private function getClassesStatistics($institutionId, $academicYearId = null)
    {
        $query = SchoolClass::where('institution_id', $institutionId)
            ->where('status', 'Aktif');

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $classes = $query->get();

        $result = [
            'grade_7' => ['count' => 0, 'total_capacity' => 0, 'total_students' => 0],
            'grade_8' => ['count' => 0, 'total_capacity' => 0, 'total_students' => 0],
            'grade_9' => ['count' => 0, 'total_capacity' => 0, 'total_students' => 0],
        ];

        foreach ($classes as $class) {
            if ($class->grade >= 7 && $class->grade <= 9) {
                $gradeKey = 'grade_' . $class->grade;
                if (isset($result[$gradeKey])) {
                    $result[$gradeKey]['count']++;
                    $result[$gradeKey]['total_capacity'] += $class->capacity ?? 0;
                    $result[$gradeKey]['total_students'] += $class->students()->count();
                }
            }
        }

        return $result;
    }

    /**
     * Get facilities statistics
     */
    private function getFacilitiesStatistics($institutionId)
    {
        // Land statistics
        $lands = Land::where('institution_id', $institutionId)->get();
        $totalLandArea = $lands->sum('area');

        // Building statistics
        $buildings = Building::where('institution_id', $institutionId)->get();
        $totalBuildingArea = $buildings->sum('building_area');

        // Room statistics by type
        $rooms = Room::where('institution_id', $institutionId)->get();
        $roomsByType = $rooms->groupBy('type')->map(function ($group) {
            return $group->count();
        })->toArray();

        return [
            'land' => [
                'count' => $lands->count(),
                'total_area' => $totalLandArea,
            ],
            'buildings' => [
                'count' => $buildings->count(),
                'total_area' => $totalBuildingArea,
            ],
            'rooms' => [
                'count' => $rooms->count(),
                'by_type' => $roomsByType,
            ],
            'total' => $lands->count() + $buildings->count() + $rooms->count(),
        ];
    }
}

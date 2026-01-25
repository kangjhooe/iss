<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Employee;
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

            // Get students statistics by grade (dynamically based on institution level)
            $studentsByGrade = $this->getStudentsByGrade($targetInstitutionId, $institution->level, $activeAcademicYearId, $month, $year);
            
            // Get employees statistics
            $employeesStats = $this->getEmployeesStatistics($targetInstitutionId);
            
            // Get facilities statistics
            $facilitiesStats = $this->getFacilitiesStatistics($targetInstitutionId);
            
            // Get classes statistics (dynamically based on institution level)
            $classesStats = $this->getClassesStatistics($targetInstitutionId, $institution->level, $activeAcademicYearId);
            
            // Get detailed classes/rombongan belajar data
            $classesDetail = $this->getClassesDetail($targetInstitutionId, $institution->level, $activeAcademicYearId);
            
            // Get students by status
            $studentsByStatus = $this->getStudentsByStatus($targetInstitutionId, $activeAcademicYearId);
            
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
                        'students' => $this->getStudentsByGrade($targetInstitutionId, $institution->level, $previousAcademicYear->id, null, null),
                        'employees' => $this->getEmployeesStatistics($targetInstitutionId),
                        'classes' => $this->getClassesStatistics($targetInstitutionId, $institution->level, $previousAcademicYear->id),
                    ];
                }
            }

            // Calculate summary statistics
            $totalStudents = array_sum(array_column($studentsByGrade, 'total'));
            $totalEmployees = $employeesStats['total'];
            $totalTeachers = $employeesStats['teachers'];
            $totalStaff = $employeesStats['staff'];
            $totalClasses = array_sum(array_column($classesStats, 'count'));
            $totalActiveStudents = $studentsByStatus['Aktif'] ?? 0;
            
            $summary = [
                'total_students' => $totalStudents,
                'total_active_students' => $totalActiveStudents,
                'total_employees' => $totalEmployees,
                'total_teachers' => $totalTeachers,
                'total_staff' => $totalStaff,
                'total_classes' => $totalClasses,
                'total_facilities' => $facilitiesStats['total'],
                'student_teacher_ratio' => $totalTeachers > 0 ? round($totalActiveStudents / $totalTeachers, 2) : 0,
                'average_students_per_class' => $totalClasses > 0 ? round($totalActiveStudents / $totalClasses, 2) : 0,
                'average_students_per_rombel' => $classesDetail['total_rombel'] > 0 ? round($totalActiveStudents / $classesDetail['total_rombel'], 2) : 0,
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
                    'students_by_status' => $studentsByStatus,
                    'employees' => $employeesStats,
                    'classes' => $classesStats,
                    'classes_detail' => $classesDetail,
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
     * Get grade range based on institution level
     */
    private function getGradeRange($level)
    {
        // Map institution levels to grade ranges
        $gradeRanges = [
            'TK' => [1, 1], // Taman Kanak-kanak (usually no grades, but we'll use 1)
            'PAUD' => [1, 1], // Pendidikan Anak Usia Dini
            'SD' => [1, 6], // Sekolah Dasar
            'MI' => [1, 6], // Madrasah Ibtidaiyah
            'SMP' => [7, 9], // Sekolah Menengah Pertama
            'MTs' => [7, 9], // Madrasah Tsanawiyah
            'SMA' => [10, 12], // Sekolah Menengah Atas
            'MA' => [10, 12], // Madrasah Aliyah
            'SMK' => [10, 12], // Sekolah Menengah Kejuruan
            'MAK' => [10, 12], // Madrasah Aliyah Kejuruan
        ];

        // Default to SMP/MTs range if level not found
        return $gradeRanges[$level] ?? [7, 9];
    }

    /**
     * Get students statistics by grade
     */
    private function getStudentsByGrade($institutionId, $institutionLevel, $academicYearId = null, $month = null, $year = null)
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
        
        // Get grade range based on institution level
        [$minGrade, $maxGrade] = $this->getGradeRange($institutionLevel);
        
        // Initialize result structure dynamically
        $result = [];
        for ($grade = $minGrade; $grade <= $maxGrade; $grade++) {
            $result['grade_' . $grade] = ['male' => 0, 'female' => 0, 'total' => 0];
        }

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
                    $attributes = $student->getAttributes();
                    $classStr = $attributes['class'] ?? $attributes['class_name'] ?? '';
                    
                    // Try to extract any grade number
                    if (preg_match('/\b(\d+)\b/', $classStr, $matches)) {
                        $grade = (int)$matches[1];
                    } elseif (preg_match('/kelas\s*(\d+)/i', $classStr, $matches)) {
                        $grade = (int)$matches[1];
                    } elseif (preg_match('/class\s*(\d+)/i', $classStr, $matches)) {
                        $grade = (int)$matches[1];
                    }
                } catch (\Exception $e) {
                    // If class field doesn't exist, log and continue
                    Log::debug('Student grade extraction failed', [
                        'student_id' => $student->id,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            // Check if grade is within the valid range for this institution level
            if ($grade !== null && $grade >= $minGrade && $grade <= $maxGrade) {
                $gradeKey = 'grade_' . $grade;
                if (isset($result[$gradeKey])) {
                    if ($student->gender === 'L' || $student->gender === 'Laki-laki' || $student->gender === 'Male') {
                        $result[$gradeKey]['male']++;
                    } else {
                        $result[$gradeKey]['female']++;
                    }
                    $result[$gradeKey]['total']++;
                }
            } else {
                // Log students that couldn't be categorized
                Log::debug('Student grade not in valid range', [
                    'student_id' => $student->id,
                    'grade' => $grade,
                    'institution_level' => $institutionLevel,
                    'valid_range' => [$minGrade, $maxGrade],
                    'class_id' => $student->class_id,
                    'class_string' => $student->getAttributes()['class'] ?? null
                ]);
            }
        }
        
        // Log final result for debugging
        Log::debug('Students by grade result', [
            'institution_id' => $institutionId,
            'institution_level' => $institutionLevel,
            'academic_year_id' => $academicYearId,
            'grade_range' => [$minGrade, $maxGrade],
            'total_students_processed' => $students->count(),
            'result' => $result
        ]);

        return $result;
    }

    /**
     * Get employees statistics
     */
    private function getEmployeesStatistics($institutionId)
    {
        $employees = Employee::where('institution_id', $institutionId)
            ->where('status', 'Aktif')
            ->get();

        $teachers = $employees->where('type', 'Guru');
        $staff = $employees->where('type', '!=', 'Guru');
        
        $male = $employees->where('gender', 'L')->count();
        $female = $employees->where('gender', 'P')->count();
        
        $maleTeachers = $teachers->where('gender', 'L')->count();
        $femaleTeachers = $teachers->where('gender', 'P')->count();
        
        $maleStaff = $staff->where('gender', 'L')->count();
        $femaleStaff = $staff->where('gender', 'P')->count();

        return [
            'total' => $employees->count(),
            'teachers' => $teachers->count(),
            'staff' => $staff->count(),
            'male' => $male,
            'female' => $female,
            'teachers_male' => $maleTeachers,
            'teachers_female' => $femaleTeachers,
            'staff_male' => $maleStaff,
            'staff_female' => $femaleStaff,
            'by_type' => $employees->groupBy('type')->map->count(),
        ];
    }

    /**
     * Get classes statistics
     */
    private function getClassesStatistics($institutionId, $institutionLevel, $academicYearId = null)
    {
        $query = SchoolClass::where('institution_id', $institutionId)
            ->where('status', 'Aktif');

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $classes = $query->get();

        // Get grade range based on institution level
        [$minGrade, $maxGrade] = $this->getGradeRange($institutionLevel);
        
        // Initialize result structure dynamically
        $result = [];
        for ($grade = $minGrade; $grade <= $maxGrade; $grade++) {
            $result['grade_' . $grade] = ['count' => 0, 'total_capacity' => 0, 'total_students' => 0];
        }

        foreach ($classes as $class) {
            if ($class->grade !== null && $class->grade >= $minGrade && $class->grade <= $maxGrade) {
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
     * Get detailed classes/rombongan belajar statistics
     */
    private function getClassesDetail($institutionId, $institutionLevel, $academicYearId = null)
    {
        $query = SchoolClass::where('institution_id', $institutionId)
            ->where('status', 'Aktif');

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $classes = $query->with(['teacher:id,name', 'room:id,name,code'])
            ->get();

        // Get grade range based on institution level
        [$minGrade, $maxGrade] = $this->getGradeRange($institutionLevel);
        
        $rombelByGrade = [];
        $totalRombel = 0;
        $totalCapacity = 0;
        $totalStudents = 0;
        $rombelList = [];

        foreach ($classes as $class) {
            if ($class->grade !== null && $class->grade >= $minGrade && $class->grade <= $maxGrade) {
                $gradeKey = 'grade_' . $class->grade;
                
                if (!isset($rombelByGrade[$gradeKey])) {
                    $rombelByGrade[$gradeKey] = [];
                }
                
                $studentCount = $class->students()->where('status', 'Aktif')->count();
                $capacity = $class->capacity ?? 0;
                
                $rombelByGrade[$gradeKey][] = [
                    'name' => $class->name,
                    'code' => $class->code,
                    'wali_kelas' => $class->teacher ? $class->teacher->name : '-',
                    'room' => $class->room ? $class->room->name : '-',
                    'students' => $studentCount,
                    'capacity' => $capacity,
                    'utilization' => $capacity > 0 ? round(($studentCount / $capacity) * 100, 2) : 0,
                ];
                
                $totalRombel++;
                $totalCapacity += $capacity;
                $totalStudents += $studentCount;
            }
        }

        return [
            'by_grade' => $rombelByGrade,
            'total_rombel' => $totalRombel,
            'total_capacity' => $totalCapacity,
            'total_students' => $totalStudents,
            'average_capacity' => $totalRombel > 0 ? round($totalCapacity / $totalRombel, 2) : 0,
            'average_students_per_rombel' => $totalRombel > 0 ? round($totalStudents / $totalRombel, 2) : 0,
        ];
    }

    /**
     * Get students statistics by status
     */
    private function getStudentsByStatus($institutionId, $academicYearId = null)
    {
        $query = Student::where('institution_id', $institutionId);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $students = $query->get();
        
        $statusCounts = $students->groupBy('status')->map->count();
        
        // Ensure common statuses exist
        $result = [
            'Aktif' => $statusCounts['Aktif'] ?? 0,
            'Lulus' => $statusCounts['Lulus'] ?? 0,
            'Pindah' => $statusCounts['Pindah'] ?? 0,
            'Drop Out' => $statusCounts['Drop Out'] ?? 0,
            'Lainnya' => 0,
        ];
        
        // Count other statuses
        foreach ($statusCounts as $status => $count) {
            if (!isset($result[$status])) {
                $result['Lainnya'] += $count;
            }
        }
        
        $result['total'] = $students->count();
        
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

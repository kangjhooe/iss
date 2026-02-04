<?php

use App\Http\Controllers\API\AcademicYearController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClassController;
use App\Http\Controllers\API\CorrespondenceController;
use App\Http\Controllers\API\AttachmentController;
use App\Http\Controllers\API\CorrespondenceStatisticsController;
use App\Http\Controllers\API\CorrespondenceExportController;
use App\Http\Controllers\API\CorrespondenceImportController;
use App\Http\Controllers\API\DispositionController;
use App\Http\Controllers\API\FacilityController;
use App\Http\Controllers\API\InstitutionChangeRequestController;
use App\Http\Controllers\API\InstitutionController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\SemesterController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\BukuIndukController;
use App\Http\Controllers\API\AlumniController;
use App\Http\Controllers\API\AlumniDestinationController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\StudentMutationController;
use App\Http\Controllers\API\ViolationController;
use App\Http\Controllers\API\ViolationTypeController;
use App\Http\Controllers\API\CounselingController;
use App\Http\Controllers\API\CounselingTypeController;
use App\Http\Controllers\API\AchievementController;
use App\Http\Controllers\API\AchievementTypeController;
use App\Http\Controllers\API\PointThresholdController;
use App\Http\Controllers\API\StudentActionLogController;
use App\Http\Controllers\API\StudentPointController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\EmployeeInstitutionAssignmentController;
use App\Http\Controllers\API\InventoryController;
use App\Http\Controllers\API\InventoryCategoryController;
use App\Http\Controllers\API\InventoryTransactionController;
use App\Http\Controllers\API\InventoryMaintenanceController;
use App\Http\Controllers\API\InventoryLoanController;
use App\Http\Controllers\API\InventoryReportController;
use App\Http\Controllers\API\TeacherDashboardController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\AdditionalDutyController;
use App\Http\Controllers\API\AuditLogController;
use App\Http\Controllers\API\SubjectController;
use App\Http\Controllers\API\LessonScheduleController;
use App\Http\Controllers\API\TeachingJournalController;
use App\Http\Controllers\API\GradeController;
use App\Http\Controllers\API\DigitalArchiveController;
use App\Http\Controllers\API\StudentAttendanceController;
use App\Http\Controllers\API\EmployeeAttendanceController;
use App\Http\Controllers\API\GuestVisitController;
use App\Http\Controllers\API\DocumentPickupController;
use App\Http\Controllers\API\ExtracurricularController;
use App\Http\Controllers\API\LibraryBookCategoryController;
use App\Http\Controllers\API\LibraryBookController;
use App\Http\Controllers\API\LibraryBookCopyController;
use App\Http\Controllers\API\LibraryLoanController;
use App\Http\Controllers\API\LibraryFinePaymentController;
use App\Http\Controllers\API\LibraryReportController;
use Illuminate\Support\Facades\Route;

// API Info route
Route::get('/', function () {
    return response()->json([
        'message' => 'Indonesia Smart School API',
        'version' => '1.0.0',
        'endpoints' => [
            'public' => [
                'POST /api/v1/register' => 'Register new institution',
                'POST /api/v1/login' => 'Login user',
                'POST /api/v1/forgot-password' => 'Request password reset',
                'POST /api/v1/reset-password' => 'Reset password',
                'POST /api/v1/verify-email' => 'Verify email address',
                'POST /api/v1/resend-verification' => 'Resend verification email',
                'POST /api/v1/refresh-token' => 'Refresh access token',
            ],
            'protected' => [
                'POST /api/v1/logout' => 'Logout user',
                'GET /api/v1/me' => 'Get current user',
                'GET /api/v1/teacher/dashboard' => 'Teacher dashboard summary',
                'GET /api/v1/institution' => 'List institutions',
                'GET /api/v1/institution/my' => 'Get my institution',
                'GET /api/v1/institution/{id}' => 'Get institution detail',
                'POST /api/v1/institution' => 'Create institution',
                'PUT /api/v1/institution/{id}' => 'Update institution',
                'DELETE /api/v1/institution/{id}' => 'Delete institution',
                'GET /api/v1/student' => 'List students',
                'GET /api/v1/student/{id}' => 'Get student detail',
                'POST /api/v1/student' => 'Create student',
                'PUT /api/v1/student/{id}' => 'Update student',
                'DELETE /api/v1/student/{id}' => 'Delete student',
                'GET /api/v1/employee' => 'List employees',
                'GET /api/v1/employee/{id}' => 'Get employee detail',
                'POST /api/v1/employee' => 'Create employee',
                'PUT /api/v1/employee/{id}' => 'Update employee',
                'DELETE /api/v1/employee/{id}' => 'Delete employee',
                'GET /api/v1/class' => 'List classes',
                'GET /api/v1/class/{id}' => 'Get class detail',
                'POST /api/v1/class' => 'Create class',
                'PUT /api/v1/class/{id}' => 'Update class',
                'DELETE /api/v1/class/{id}' => 'Delete class',
            ],
        ],
        'note' => 'Protected routes require Bearer token in Authorization header',
    ]);
});

// Public routes with rate limiting
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerificationEmail']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
});

// Protected routes with rate limiting
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::get('/permissions/teachers', [PermissionController::class, 'getTeachers']);
    Route::put('/permissions/users/{userId}', [PermissionController::class, 'updateUserPermissions']);
    Route::get('/additional-duties', [AdditionalDutyController::class, 'index']);
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/filter-options', [AuditLogController::class, 'filterOptions'])->name('audit-logs.filter-options');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');

    // Institution routes
    Route::middleware('module:institution')->group(function () {
        Route::get('/institution/my', [InstitutionController::class, 'myInstitution']);
        Route::put('/institution/{id}/active-academic-year', [InstitutionController::class, 'updateActiveAcademicYear'])->name('institution.update-active-academic-year');
        Route::post('/institution/{id}/logo', [InstitutionController::class, 'uploadLogo'])->name('institution.upload-logo');
        Route::get('/institution/{id}/logo', [InstitutionController::class, 'getLogo'])->name('institution.get-logo');
        Route::apiResource('institution', InstitutionController::class);
    });

    // Student routes (list tanpa cache agar tambah/edit/import langsung muncul)
    Route::middleware('module:student')->group(function () {
        Route::get('/student', [StudentController::class, 'index']);
        Route::get('/student/{id}', [StudentController::class, 'show']);
        Route::get('/student/{id}/buku-induk', [BukuIndukController::class, 'show'])->name('student.buku-induk');
        Route::get('/student/{id}/buku-induk/pdf', [BukuIndukController::class, 'print'])->name('student.buku-induk.pdf');
        Route::post('/student', [StudentController::class, 'store']);
        Route::put('/student/{id}', [StudentController::class, 'update']);
        Route::delete('/student/{id}', [StudentController::class, 'destroy']);
    Route::post('/student/{id}/restore', [StudentController::class, 'restore']);
        Route::post('/student/promote', [StudentController::class, 'promote'])->name('student.promote');
        Route::post('/student/import', [StudentController::class, 'import'])->name('student.import');
        Route::post('/student/{id}/documents', [StudentController::class, 'uploadDocument'])->name('student.upload-document');
        Route::delete('/student/{id}/documents/{documentId}', [StudentController::class, 'deleteDocument'])->name('student.delete-document');
        Route::get('/student/{id}/documents/{documentId}/download', [StudentController::class, 'downloadDocument'])->name('student.download-document');
        // Alumni (lulusan)
        Route::get('/alumni', [AlumniController::class, 'index']);
        Route::get('/alumni/graduation-years', [AlumniController::class, 'graduationYears']);
        Route::post('/student/{id}/graduate', [AlumniController::class, 'graduate']);
        Route::post('/student/graduate-bulk', [AlumniController::class, 'graduateBulk']);
        // Tracking destinasi alumni (lanjut sekolah/kuliah/kerja/dll)
        Route::get('/alumni/destination-types', [AlumniDestinationController::class, 'types'])->name('alumni.destination-types');
        Route::get('/alumni/students/{studentId}/destinations', [AlumniDestinationController::class, 'indexByStudent'])->name('alumni.destinations.by-student');
        Route::post('/alumni-destinations', [AlumniDestinationController::class, 'store'])->name('alumni-destinations.store');
        Route::put('/alumni-destinations/{alumni_destination}', [AlumniDestinationController::class, 'update'])->name('alumni-destinations.update');
        Route::delete('/alumni-destinations/{alumni_destination}', [AlumniDestinationController::class, 'destroy'])->name('alumni-destinations.destroy');
        // Student mutation (mutasi siswa)
        Route::get('/student-mutations/target-institutions', [StudentMutationController::class, 'searchTargetInstitutions'])->name('student-mutations.target-institutions');
        Route::get('/student-mutations/origin-institutions', [StudentMutationController::class, 'searchOriginInstitutions'])->name('student-mutations.origin-institutions');
        Route::post('/student-mutations/pull', [StudentMutationController::class, 'pull'])->name('student-mutations.pull');
        Route::post('/student-mutations/{student_mutation}/approve', [StudentMutationController::class, 'approve'])->name('student-mutations.approve');
        Route::get('/student-mutations', [StudentMutationController::class, 'index']);
        Route::get('/student-mutations/report', [StudentMutationController::class, 'report'])->name('student-mutations.report');
        Route::get('/student-mutations/export', [StudentMutationController::class, 'export'])->name('student-mutations.export');
        Route::get('/student-mutations/by-student/{student_id}', [StudentMutationController::class, 'historyByStudent'])->name('student-mutations.by-student');
        Route::get('/student-mutations/history-by-nisn', [StudentMutationController::class, 'historyByNisn'])->name('student-mutations.history-by-nisn');
        Route::post('/student-mutations', [StudentMutationController::class, 'store']);
        Route::get('/student-mutations/{student_mutation}', [StudentMutationController::class, 'show']);
    });

    // Violation routes (Pelanggaran)
    Route::middleware('module:violation')->group(function () {
        Route::get('/violations', [ViolationController::class, 'index']);
        Route::post('/violations', [ViolationController::class, 'store']);
        Route::get('/violations/by-student/{studentId}', [ViolationController::class, 'byStudent'])->name('violations.by-student');
        Route::get('/violations/{violation}', [ViolationController::class, 'show']);
        Route::put('/violations/{violation}', [ViolationController::class, 'update']);
        Route::delete('/violations/{violation}', [ViolationController::class, 'destroy']);
        Route::get('/violation-types', [ViolationTypeController::class, 'index']);
        Route::post('/violation-types', [ViolationTypeController::class, 'store']);
        Route::get('/violation-types/{violation_type}', [ViolationTypeController::class, 'show']);
        Route::put('/violation-types/{violation_type}', [ViolationTypeController::class, 'update']);
        Route::delete('/violation-types/{violation_type}', [ViolationTypeController::class, 'destroy']);
        // Prestasi (achievement) - pengurangan poin
        Route::get('/achievements', [AchievementController::class, 'index']);
        Route::post('/achievements', [AchievementController::class, 'store']);
        Route::get('/achievements/by-student/{studentId}', [AchievementController::class, 'byStudent']);
        Route::get('/achievements/{achievement}', [AchievementController::class, 'show']);
        Route::put('/achievements/{achievement}', [AchievementController::class, 'update']);
        Route::delete('/achievements/{achievement}', [AchievementController::class, 'destroy']);
        Route::get('/achievement-types', [AchievementTypeController::class, 'index']);
        Route::post('/achievement-types', [AchievementTypeController::class, 'store']);
        Route::get('/achievement-types/{achievement_type}', [AchievementTypeController::class, 'show']);
        Route::put('/achievement-types/{achievement_type}', [AchievementTypeController::class, 'update']);
        Route::delete('/achievement-types/{achievement_type}', [AchievementTypeController::class, 'destroy']);
        // Aturan tindakan (threshold poin)
        Route::get('/point-thresholds', [PointThresholdController::class, 'index']);
        Route::post('/point-thresholds', [PointThresholdController::class, 'store']);
        Route::get('/point-thresholds/{point_threshold}', [PointThresholdController::class, 'show']);
        Route::put('/point-thresholds/{point_threshold}', [PointThresholdController::class, 'update']);
        Route::delete('/point-thresholds/{point_threshold}', [PointThresholdController::class, 'destroy']);
        // Catatan tindakan (panggilan orang tua, dll.)
        Route::get('/student-action-logs', [StudentActionLogController::class, 'index']);
        Route::post('/student-action-logs', [StudentActionLogController::class, 'store']);
        Route::get('/student-action-logs/by-student/{studentId}', [StudentActionLogController::class, 'byStudent']);
        // Ringkasan poin siswa + tindakan yang harus dilakukan
        Route::get('/student-points', [StudentPointController::class, 'index']);
        Route::get('/student-points/{studentId}/summary', [StudentPointController::class, 'summary']);
    });

    // Counseling routes (Konseling)
    Route::middleware('module:counseling')->group(function () {
        Route::get('/counseling', [CounselingController::class, 'index']);
        Route::get('/counseling/stats', [CounselingController::class, 'stats'])->name('counseling.stats');
        Route::get('/counseling/upcoming', [CounselingController::class, 'upcoming'])->name('counseling.upcoming');
        Route::get('/counseling/export', [CounselingController::class, 'export'])->name('counseling.export');
        Route::get('/counseling/counselors', [CounselingController::class, 'counselors'])->name('counseling.counselors');
        Route::post('/counseling', [CounselingController::class, 'store']);
        Route::get('/counseling/by-student/{studentId}', [CounselingController::class, 'byStudent'])->name('counseling.by-student');
        Route::get('/counseling/{counseling_session}', [CounselingController::class, 'show']);
        Route::put('/counseling/{counseling_session}', [CounselingController::class, 'update']);
        Route::delete('/counseling/{counseling_session}', [CounselingController::class, 'destroy']);
        Route::get('/counseling-types', [CounselingTypeController::class, 'index']);
        Route::post('/counseling-types', [CounselingTypeController::class, 'store']);
        Route::get('/counseling-types/{counseling_type}', [CounselingTypeController::class, 'show']);
        Route::put('/counseling-types/{counseling_type}', [CounselingTypeController::class, 'update']);
        Route::delete('/counseling-types/{counseling_type}', [CounselingTypeController::class, 'destroy']);
    });

    // Jurnal Mengajar (Teaching Journal) + Absensi Siswa per jam
    Route::middleware('module:teaching_journal')->group(function () {
        Route::get('/teaching-journals', [TeachingJournalController::class, 'index'])->name('teaching-journals.index');
        Route::post('/teaching-journals', [TeachingJournalController::class, 'store'])->name('teaching-journals.store');
        Route::get('/teaching-journals/{teaching_journal}', [TeachingJournalController::class, 'show'])->name('teaching-journals.show');
        Route::put('/teaching-journals/{teaching_journal}', [TeachingJournalController::class, 'update'])->name('teaching-journals.update');
        Route::delete('/teaching-journals/{teaching_journal}', [TeachingJournalController::class, 'destroy'])->name('teaching-journals.destroy');
        Route::get('/teaching-journals/{teachingJournalId}/attendances', [StudentAttendanceController::class, 'index'])->name('teaching-journals.attendances.index');
        Route::post('/teaching-journals/attendances', [StudentAttendanceController::class, 'store'])->name('teaching-journals.attendances.store');
        Route::put('/student-attendances/{student_attendance}', [StudentAttendanceController::class, 'update'])->name('student-attendances.update');
    });

    // Absensi (Guru & Staff per hari)
    Route::middleware('module:attendance')->group(function () {
        Route::get('/employee-attendances/status-options', [EmployeeAttendanceController::class, 'statusOptions'])->name('employee-attendances.status-options');
        Route::post('/employee-attendances/bulk', [EmployeeAttendanceController::class, 'bulkStore'])->name('employee-attendances.bulk');
        Route::get('/employee-attendances', [EmployeeAttendanceController::class, 'index'])->name('employee-attendances.index');
        Route::post('/employee-attendances', [EmployeeAttendanceController::class, 'store'])->name('employee-attendances.store');
        Route::put('/employee-attendances/{employee_attendance}', [EmployeeAttendanceController::class, 'update'])->name('employee-attendances.update');
    });

    // Buku Nilai (Grade Book)
    Route::middleware('module:grade_book')->group(function () {
        Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
        Route::get('/grades/by-class-subject-semester', [GradeController::class, 'getByClassSubjectSemester'])->name('grades.by-class-subject-semester');
        Route::get('/grades/by-student-semester', [GradeController::class, 'getByStudentSemester'])->name('grades.by-student-semester');
        Route::get('/grades/export', [GradeController::class, 'export'])->name('grades.export');
        Route::get('/grades/export-student-raport', [GradeController::class, 'exportStudentRaport'])->name('grades.export-student-raport');
        Route::post('/grades/bulk', [GradeController::class, 'bulkUpsert'])->name('grades.bulk');
        Route::post('/grades', [GradeController::class, 'store'])->name('grades.store');
        Route::get('/grades/{grade}', [GradeController::class, 'show'])->name('grades.show');
        Route::put('/grades/{grade}', [GradeController::class, 'update'])->name('grades.update');
        Route::delete('/grades/{grade}', [GradeController::class, 'destroy'])->name('grades.destroy');
    });

    // Employee routes with caching
    Route::middleware('module:teacher')->group(function () {
        Route::middleware('cache:300')->group(function () {
            Route::get('/employee', [EmployeeController::class, 'index']);
        });
        Route::get('/employee/search', [EmployeeController::class, 'searchByNik']);
        Route::get('/employee/{id}', [EmployeeController::class, 'show']);
        Route::post('/employee', [EmployeeController::class, 'store']);
        Route::put('/employee/{id}', [EmployeeController::class, 'update']);
        Route::delete('/employee/{id}', [EmployeeController::class, 'destroy']);
    Route::post('/employee/{id}/restore', [EmployeeController::class, 'restore']);
        Route::post('/employee/{id}/reset-password', [EmployeeController::class, 'resetPasswordByAdmin'])->name('employee.reset-password');
        Route::post('/employee/import', [EmployeeController::class, 'import'])->name('employee.import');
        Route::post('/employee/{id}/documents', [EmployeeController::class, 'uploadDocument'])->name('employee.upload-document');
        Route::delete('/employee/{id}/documents/{documentId}', [EmployeeController::class, 'deleteDocument'])->name('employee.delete-document');
        Route::get('/employee/{id}/documents/{documentId}/download', [EmployeeController::class, 'downloadDocument'])->name('employee.download-document');
        Route::get('/employee-assignments/pending', [EmployeeInstitutionAssignmentController::class, 'pending']);
        Route::post('/employee/{employee}/assignments', [EmployeeInstitutionAssignmentController::class, 'store']);
        Route::put('/employee-assignments/{assignment}', [EmployeeInstitutionAssignmentController::class, 'update']);
        Route::post('/employee-assignments/{assignment}/approve', [EmployeeInstitutionAssignmentController::class, 'approve']);
        Route::post('/employee-assignments/{assignment}/reject', [EmployeeInstitutionAssignmentController::class, 'reject']);
        Route::post('/employee-assignments/{assignment}/end', [EmployeeInstitutionAssignmentController::class, 'end']);
    });

    // Class routes with caching
    Route::middleware('module:class')->group(function () {
        Route::middleware('cache:300')->group(function () {
            Route::get('/class', [ClassController::class, 'index']);
        });
        Route::get('/class/{id}', [ClassController::class, 'show']);
        Route::post('/class', [ClassController::class, 'store']);
        Route::put('/class/{id}', [ClassController::class, 'update']);
        Route::delete('/class/{id}', [ClassController::class, 'destroy']);
        Route::get('/class/{id}/available-students', [ClassController::class, 'getAvailableStudents'])->name('class.available-students');
        Route::get('/class/{id}/students', [ClassController::class, 'getStudents'])->name('class.students');
        Route::post('/class/{id}/students', [ClassController::class, 'addStudents'])->name('class.add-students');
        Route::delete('/class/{id}/students/{studentId}', [ClassController::class, 'removeStudent'])->name('class.remove-student');
        Route::get('/class/export/pdf', [ClassController::class, 'exportPdf'])->name('class.export.pdf');
    });

    // Jadwal Pelajaran (Schedule) routes
    Route::middleware('module:schedule')->group(function () {
        Route::apiResource('subjects', SubjectController::class);
        Route::get('/lesson-schedules/by-class/{classId}', [LessonScheduleController::class, 'byClass'])->name('lesson-schedules.by-class');
        Route::get('/lesson-schedules/by-teacher/{employeeId}', [LessonScheduleController::class, 'byTeacher'])->name('lesson-schedules.by-teacher');
        Route::get('/lesson-schedules/by-room/{roomId}', [LessonScheduleController::class, 'byRoom'])->name('lesson-schedules.by-room');
        Route::post('/lesson-schedules/copy-semester', [LessonScheduleController::class, 'copySemester'])->name('lesson-schedules.copy-semester');
        Route::delete('/lesson-schedules/by-class/{classId}', [LessonScheduleController::class, 'deleteByClass'])->name('lesson-schedules.delete-by-class');
        Route::delete('/lesson-schedules/by-semester/{semesterId}', [LessonScheduleController::class, 'deleteBySemester'])->name('lesson-schedules.delete-by-semester');
        Route::apiResource('lesson-schedules', LessonScheduleController::class);
    });

    // Academic Year routes
    // Index route accessible to all authenticated users (for selecting academic years)
    Route::get('/academic-years', [AcademicYearController::class, 'index'])->name('academic-years.index');
    Route::get('/academic-years/{id}', [AcademicYearController::class, 'show'])->name('academic-years.show');
    
    // Super Admin only routes for managing academic years
    Route::middleware(\App\Http\Middleware\EnsureSuperAdmin::class)->group(function () {
        Route::get('/academic-years/active', [AcademicYearController::class, 'active'])->name('academic-years.active');
        Route::get('/academic-years/current', [AcademicYearController::class, 'current'])->name('academic-years.current');
        Route::post('/academic-years/{id}/activate', [AcademicYearController::class, 'activate'])->name('academic-years.activate');
        Route::post('/academic-years', [AcademicYearController::class, 'store'])->name('academic-years.store');
        Route::put('/academic-years/{id}', [AcademicYearController::class, 'update'])->name('academic-years.update');
        Route::delete('/academic-years/{id}', [AcademicYearController::class, 'destroy'])->name('academic-years.destroy');
    });

    // Semester routes
    Route::get('/semesters/active', [SemesterController::class, 'active'])->name('semesters.active');
    Route::get('/semesters/academic-year/{academicYearId}', [SemesterController::class, 'byAcademicYear'])->name('semesters.by-academic-year');
    Route::get('/semesters/academic-year/{academicYearId}/active', [SemesterController::class, 'activeForAcademicYear'])->name('semesters.active-for-academic-year');
    Route::post('/semesters/academic-year/{academicYearId}/auto-generate', [SemesterController::class, 'autoGenerate'])->name('semesters.auto-generate');
    Route::post('/semesters/{id}/activate', [SemesterController::class, 'activate'])->name('semesters.activate');
    Route::apiResource('semesters', SemesterController::class);

    // Institution change request routes
    Route::get('/institution-change-requests/pending-count', [InstitutionChangeRequestController::class, 'pendingCount'])->name('institution-change-requests.pending-count');
    Route::post('/institution-change-requests/{id}/approve', [InstitutionChangeRequestController::class, 'approve'])->name('institution-change-requests.approve');
    Route::apiResource('institution-change-requests', InstitutionChangeRequestController::class)->except(['update', 'destroy']);

    // Facility routes (Sarana Prasarana)
    Route::prefix('facility')->middleware('module:facility')->group(function () {
        // Land routes
        Route::get('/lands', [FacilityController::class, 'getLands']);
        Route::post('/lands', [FacilityController::class, 'createLand']);
        Route::put('/lands/{id}', [FacilityController::class, 'updateLand']);
        Route::delete('/lands/{id}', [FacilityController::class, 'deleteLand']);
        
        // Building routes
        Route::get('/buildings', [FacilityController::class, 'getBuildings']);
        Route::post('/buildings', [FacilityController::class, 'createBuilding']);
        Route::put('/buildings/{id}', [FacilityController::class, 'updateBuilding']);
        Route::delete('/buildings/{id}', [FacilityController::class, 'deleteBuilding']);
        
        // Room routes
        Route::get('/rooms', [FacilityController::class, 'getRooms']);
        Route::post('/rooms', [FacilityController::class, 'createRoom']);
        Route::put('/rooms/{id}', [FacilityController::class, 'updateRoom']);
        Route::delete('/rooms/{id}', [FacilityController::class, 'deleteRoom']);
        Route::get('/lab-report', [FacilityController::class, 'getLabReport']);
        Route::get('/my-labs', [FacilityController::class, 'getMyLabs']);
    });

    // Inventory routes (Inventaris)
    Route::prefix('inventory')->middleware('module:inventory')->group(function () {
        // Category routes
        Route::apiResource('categories', InventoryCategoryController::class);
        
        // Item routes
        Route::apiResource('items', InventoryController::class);
        Route::post('/items/{id}/restore', [InventoryController::class, 'restore']);
        
        // Transaction routes
        Route::get('/transactions', [InventoryTransactionController::class, 'index'])->name('inventory.transactions.index');
        Route::post('/transactions', [InventoryTransactionController::class, 'store'])->name('inventory.transactions.store');
        Route::get('/transactions/{transaction}', [InventoryTransactionController::class, 'show'])->name('inventory.transactions.show');
        
        // Maintenance routes
        Route::get('/maintenances', [InventoryMaintenanceController::class, 'index'])->name('inventory.maintenances.index');
        Route::post('/maintenances', [InventoryMaintenanceController::class, 'store'])->name('inventory.maintenances.store');
        Route::get('/maintenances/{maintenance}', [InventoryMaintenanceController::class, 'show'])->name('inventory.maintenances.show');
        Route::put('/maintenances/{maintenance}', [InventoryMaintenanceController::class, 'update'])->name('inventory.maintenances.update');
        
        // Loan routes
        Route::get('/loans', [InventoryLoanController::class, 'index'])->name('inventory.loans.index');
        Route::post('/loans', [InventoryLoanController::class, 'store'])->name('inventory.loans.store');
        Route::get('/loans/{loan}', [InventoryLoanController::class, 'show'])->name('inventory.loans.show');
        Route::post('/loans/{loan}/return', [InventoryLoanController::class, 'return'])->name('inventory.loans.return');
        
        // Report routes
        Route::prefix('reports')->group(function () {
            Route::get('/statistics', [InventoryReportController::class, 'statistics'])->name('inventory.reports.statistics');
            Route::get('/by-category', [InventoryReportController::class, 'byCategory'])->name('inventory.reports.by-category');
            Route::get('/by-location', [InventoryReportController::class, 'byLocation'])->name('inventory.reports.by-location');
            Route::get('/damaged-missing', [InventoryReportController::class, 'damagedMissing'])->name('inventory.reports.damaged-missing');
            Route::get('/loaned', [InventoryReportController::class, 'loaned'])->name('inventory.reports.loaned');
            Route::get('/asset-value', [InventoryReportController::class, 'assetValue'])->name('inventory.reports.asset-value');
            Route::get('/maintenance', [InventoryReportController::class, 'maintenance'])->name('inventory.reports.maintenance');
            Route::get('/transactions', [InventoryReportController::class, 'transactions'])->name('inventory.reports.transactions');
        });
    });

    // Report routes (Laporan/Statistik)
    Route::prefix('report')->middleware('module:report')->group(function () {
        Route::get('/institution/{institutionId?}', [ReportController::class, 'getStatistics'])->name('report.statistics');
    });

    // Teacher dashboard
    Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');

    // Correspondence routes (Persuratan)
    Route::prefix('correspondence')->middleware('module:correspondence')->group(function () {
        Route::get('/categories', [CorrespondenceController::class, 'categories'])->name('correspondence.categories');
        Route::get('/letter-types', [CorrespondenceController::class, 'letterTypes'])->name('correspondence.letter-types');
        Route::get('/users', [CorrespondenceController::class, 'users'])->name('correspondence.users');
        Route::get('/statistics', [CorrespondenceStatisticsController::class, 'index'])->name('correspondence.statistics');
        Route::get('/export/excel', [CorrespondenceExportController::class, 'exportExcel'])->name('correspondence.export.excel');
        Route::get('/export/pdf', [CorrespondenceExportController::class, 'exportPdf'])->name('correspondence.export.pdf');
        Route::get('/export/download/{filePath}', [CorrespondenceExportController::class, 'downloadExcel'])->name('correspondence.export.download');
        Route::get('/export/pdf/{filePath}', [CorrespondenceExportController::class, 'downloadPdf'])->name('correspondence.export.pdf.download');
        Route::post('/import', [CorrespondenceImportController::class, 'import'])->name('correspondence.import');
        Route::get('/import/template', [CorrespondenceImportController::class, 'downloadTemplate'])->name('correspondence.import.template');
        Route::get('/{id}/print', [CorrespondenceController::class, 'print'])->name('correspondence.print');
        Route::post('/{id}/approve', [CorrespondenceController::class, 'approve'])->name('correspondence.approve');
        Route::post('/{id}/send', [CorrespondenceController::class, 'send'])->name('correspondence.send');
        Route::post('/{id}/archive', [CorrespondenceController::class, 'archive'])->name('correspondence.archive');
        Route::post('/{id}/restore', [CorrespondenceController::class, 'restore'])->name('correspondence.restore');
        
        // Disposition routes
        Route::get('/{correspondenceId}/dispositions', [DispositionController::class, 'index'])->name('correspondence.dispositions.index');
        Route::post('/{correspondenceId}/dispositions', [DispositionController::class, 'store'])->name('correspondence.dispositions.store');
        Route::put('/dispositions/{id}', [DispositionController::class, 'update'])->name('correspondence.dispositions.update');
        Route::post('/dispositions/{id}/complete', [DispositionController::class, 'complete'])->name('correspondence.dispositions.complete');
        Route::delete('/dispositions/{id}', [DispositionController::class, 'destroy'])->name('correspondence.dispositions.destroy');
        
        // Attachment routes
        Route::get('/{correspondenceId}/attachments', [AttachmentController::class, 'index'])->name('correspondence.attachments.index');
        Route::post('/{correspondenceId}/attachments', [AttachmentController::class, 'store'])->name('correspondence.attachments.store');
        Route::get('/attachments/{id}/download', [AttachmentController::class, 'download'])->name('correspondence.attachments.download');
        Route::put('/attachments/{id}', [AttachmentController::class, 'update'])->name('correspondence.attachments.update');
        Route::delete('/attachments/{id}', [AttachmentController::class, 'destroy'])->name('correspondence.attachments.destroy');
    });
    Route::middleware('module:correspondence')->group(function () {
        Route::apiResource('correspondence', CorrespondenceController::class);
    });
    
    // Disposition routes (standalone)
    Route::middleware('module:correspondence')->group(function () {
        Route::get('/dispositions/pending', [DispositionController::class, 'pending'])->name('dispositions.pending');
    });

    // Digital Archive (Arsip Digital)
    Route::middleware('module:digital_archive')->group(function () {
        Route::get('digital-archives/categories', [DigitalArchiveController::class, 'categories'])->name('digital-archives.categories');
        Route::post('digital-archives/categories', [DigitalArchiveController::class, 'storeCategory'])->name('digital-archives.categories.store');
        Route::get('digital-archives/{digital_archive}/download', [DigitalArchiveController::class, 'download'])->name('digital-archives.download');
        Route::apiResource('digital-archives', DigitalArchiveController::class);
    });

    // Buku Tamu (Guest Book)
    Route::middleware('module:guest_book')->group(function () {
        Route::get('guest-visits/export', [GuestVisitController::class, 'export'])->name('guest-visits.export');
        Route::post('guest-visits/{guest_visit}/checkout', [GuestVisitController::class, 'checkout'])->name('guest-visits.checkout');
        Route::post('guest-visits/{guest_visit}', [GuestVisitController::class, 'update'])->name('guest-visits.update.post'); // FormData update
        Route::apiResource('guest-visits', GuestVisitController::class);
    });

    // Pengambilan Ijazah (Document Pickup)
    Route::middleware('module:document_pickup')->group(function () {
        Route::post('document-pickups/{document_pickup}', [DocumentPickupController::class, 'update'])->name('document-pickups.update.post'); // FormData update
        Route::apiResource('document-pickups', DocumentPickupController::class);
    });

    // Perpustakaan (Library)
    Route::prefix('library')->middleware('module:library')->group(function () {
        Route::apiResource('categories', LibraryBookCategoryController::class);
        Route::apiResource('books', LibraryBookController::class);
        Route::apiResource('copies', LibraryBookCopyController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::get('loans', [LibraryLoanController::class, 'index'])->name('library.loans.index');
        Route::post('loans', [LibraryLoanController::class, 'store'])->name('library.loans.store');
        Route::get('loans/{loan}', [LibraryLoanController::class, 'show'])->name('library.loans.show');
        Route::post('loans/{loan}/return', [LibraryLoanController::class, 'returnLoan'])->name('library.loans.return');
        Route::post('loans/{loan}/renew', [LibraryLoanController::class, 'renewLoan'])->name('library.loans.renew');
        Route::get('loans/{loan}/calculate-fine', [LibraryLoanController::class, 'calculateFine'])->name('library.loans.calculate-fine');
        Route::get('fine-payments', [LibraryFinePaymentController::class, 'index'])->name('library.fine-payments.index');
        Route::post('fine-payments', [LibraryFinePaymentController::class, 'store'])->name('library.fine-payments.store');
        Route::prefix('reports')->group(function () {
            Route::get('statistics', [LibraryReportController::class, 'statistics'])->name('library.reports.statistics');
            Route::get('top-books', [LibraryReportController::class, 'topBooks'])->name('library.reports.top-books');
            Route::get('loans-by-month', [LibraryReportController::class, 'loansByMonth'])->name('library.reports.loans-by-month');
            Route::get('export/loans-pdf', [LibraryReportController::class, 'exportLoansPdf'])->name('library.reports.export.loans-pdf');
        });
    });

    // Ekstrakurikuler
    Route::middleware('module:extracurricular')->group(function () {
        Route::get('extracurriculars/by-student/{studentId}', [ExtracurricularController::class, 'getByStudent'])->name('extracurriculars.by-student');
        Route::get('extracurriculars', [ExtracurricularController::class, 'index'])->name('extracurriculars.index');
        Route::post('extracurriculars', [ExtracurricularController::class, 'store'])->name('extracurriculars.store');
        Route::get('extracurriculars/{extracurricular}', [ExtracurricularController::class, 'show'])->name('extracurriculars.show');
        Route::put('extracurriculars/{extracurricular}', [ExtracurricularController::class, 'update'])->name('extracurriculars.update');
        Route::delete('extracurriculars/{extracurricular}', [ExtracurricularController::class, 'destroy'])->name('extracurriculars.destroy');
        Route::get('extracurriculars/{extracurricular}/students', [ExtracurricularController::class, 'getStudents'])->name('extracurriculars.students');
        Route::get('extracurriculars/{extracurricular}/students/export', [ExtracurricularController::class, 'exportParticipants'])->name('extracurriculars.students.export');
        Route::get('extracurriculars/{extracurricular}/available-students', [ExtracurricularController::class, 'getAvailableStudents'])->name('extracurriculars.available-students');
        Route::post('extracurriculars/{extracurricular}/students', [ExtracurricularController::class, 'addStudents'])->name('extracurriculars.add-students');
        Route::put('extracurriculars/{extracurricular}/enrollments/{enrollmentId}', [ExtracurricularController::class, 'updateEnrollment'])->name('extracurriculars.update-enrollment');
        Route::delete('extracurriculars/{extracurricular}/students/{studentId}', [ExtracurricularController::class, 'removeStudent'])->name('extracurriculars.remove-student');
    });
});

<?php

use App\Http\Controllers\API\AcademicCalendarController;
use App\Http\Controllers\API\AcademicYearController;
use App\Http\Controllers\API\AchievementController;
use App\Http\Controllers\API\AchievementTypeController;
use App\Http\Controllers\API\AdditionalDutyController;
use App\Http\Controllers\API\AlumniController;
use App\Http\Controllers\API\AlumniDestinationController;
use App\Http\Controllers\API\AppBrandingController;
use App\Http\Controllers\API\AsetTandaTanganController;
use App\Http\Controllers\API\AttachmentController;
use App\Http\Controllers\API\AuditLogController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BankSoalController;
use App\Http\Controllers\API\BkkApplicationController;
use App\Http\Controllers\API\BkkVacancyController;
use App\Http\Controllers\API\BkReportController;
use App\Http\Controllers\API\BukuIndukController;
use App\Http\Controllers\API\ClassController;
use App\Http\Controllers\API\CorrespondenceController;
use App\Http\Controllers\API\CorrespondenceExportController;
use App\Http\Controllers\API\CorrespondenceImportController;
use App\Http\Controllers\API\CorrespondenceStatisticsController;
use App\Http\Controllers\API\CounselingController;
use App\Http\Controllers\API\CounselingTypeController;
use App\Http\Controllers\API\DashboardChartsController;
use App\Http\Controllers\API\DigitalArchiveController;
use App\Http\Controllers\API\DispositionController;
use App\Http\Controllers\API\DocumentPickupController;
use App\Http\Controllers\API\EmployeeAttendanceController;
use App\Http\Controllers\API\EmployeeCareerHistoryController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\EmployeeDecreeController;
use App\Http\Controllers\API\EmployeeInstitutionAssignmentController;
use App\Http\Controllers\API\EmployeeLeaveController;
use App\Http\Controllers\API\EmployeePayrollPortalController;
use App\Http\Controllers\API\EmployeeStructuralPositionController;
use App\Http\Controllers\API\ExamAttemptController;
use App\Http\Controllers\API\ExamControlController;
use App\Http\Controllers\API\ExamController;
use App\Http\Controllers\API\ExamParticipantController;
use App\Http\Controllers\API\ExamSessionController;
use App\Http\Controllers\API\ExtracurricularActivityController;
use App\Http\Controllers\API\ExtracurricularController;
use App\Http\Controllers\API\FacilityController;
use App\Http\Controllers\API\FeedbackTicketController;
use App\Http\Controllers\API\FinanceDashboardController;
use App\Http\Controllers\API\FinanceExpenseController;
use App\Http\Controllers\API\FinanceFeeTypeController;
use App\Http\Controllers\API\FinanceInvoiceController;
use App\Http\Controllers\API\FinancePaymentController;
use App\Http\Controllers\API\FinancePickerController;
use App\Http\Controllers\API\GradeController;
use App\Http\Controllers\API\GradeRemedialController;
use App\Http\Controllers\API\GeocodeController;
use App\Http\Controllers\API\GuestVisitController;
use App\Http\Controllers\API\IndustryPartnerController;
use App\Http\Controllers\API\InstitutionAdminController;
use App\Http\Controllers\API\InstitutionChangeRequestController;
use App\Http\Controllers\API\InstitutionController;
use App\Http\Controllers\API\InstitutionMonetizationController;
use App\Http\Controllers\API\InventoryAssetController;
use App\Http\Controllers\API\InventoryAssetMovementController;
use App\Http\Controllers\API\InventoryCategoryController;
use App\Http\Controllers\API\InventoryController;
use App\Http\Controllers\API\InventoryDisposalController;
use App\Http\Controllers\API\InventoryLoanController;
use App\Http\Controllers\API\InventoryMaintenanceController;
use App\Http\Controllers\API\InventoryReportController;
use App\Http\Controllers\API\InventoryStockOpnameController;
use App\Http\Controllers\API\InventoryTransactionController;
use App\Http\Controllers\API\KopSuratController;
use App\Http\Controllers\API\LabBookingController;
use App\Http\Controllers\API\LabUsageJournalController;
use App\Http\Controllers\API\LessonScheduleController;
use App\Http\Controllers\API\LessonScheduleTemplateController;
use App\Http\Controllers\API\LibraryBookCategoryController;
use App\Http\Controllers\API\LibraryBookController;
use App\Http\Controllers\API\LibraryBookCopyController;
use App\Http\Controllers\API\LibraryFinePaymentController;
use App\Http\Controllers\API\LibraryLoanController;
use App\Http\Controllers\API\LibraryReportController;
use App\Http\Controllers\API\MyTeacherAppreciationController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ParentPortalController;
use App\Http\Controllers\API\PasswordResetRequestController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\PiketController;
use App\Http\Controllers\API\PklJournalController;
use App\Http\Controllers\API\PklPeriodController;
use App\Http\Controllers\API\PklPlacementController;
use App\Http\Controllers\API\PointThresholdController;
use App\Http\Controllers\API\PayrollComponentController;
use App\Http\Controllers\API\PayrollPositionAllowanceController;
use App\Http\Controllers\API\PayrollEmployeeProfileController;
use App\Http\Controllers\API\PayrollPeriodController;
use App\Http\Controllers\API\PayrollRunController;
use App\Http\Controllers\API\PayrollSlipController;
use App\Http\Controllers\API\PpdbChannelController;
use App\Http\Controllers\API\PpdbDashboardController;
use App\Http\Controllers\API\PpdbPeriodController;
use App\Http\Controllers\API\ProgramKeahlianController;
use App\Http\Controllers\API\PublicLibraryController;
use App\Http\Controllers\API\PublicPpdbController;
use App\Http\Controllers\API\PublicReleaseController;
use App\Http\Controllers\API\PublicSchoolController;
use App\Http\Controllers\API\QrAttendanceController;
use App\Http\Controllers\API\RegionController;
use App\Http\Controllers\API\QuestionAssetController;
use App\Http\Controllers\API\QuestionBankController;
use App\Http\Controllers\API\QuestionStimulusController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\SchoolPostController;
use App\Http\Controllers\API\SemesterController;
use App\Http\Controllers\API\StudentActionLogController;
use App\Http\Controllers\API\StudentAttendanceController;
use App\Http\Controllers\API\StudentChangeRequestController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\StudentFinanceController;
use App\Http\Controllers\API\StudentMutationController;
use App\Http\Controllers\API\StudentNisController;
use App\Http\Controllers\API\StudentPointController;
use App\Http\Controllers\API\SubjectController;
use App\Http\Controllers\API\SuperAdminAdoptionController;
use App\Http\Controllers\API\SuperAdminBroadcastController;
use App\Http\Controllers\API\SuperAdminDashboardController;
use App\Http\Controllers\API\SuperAdminDatabaseBackupController;
use App\Http\Controllers\API\InstitutionImpersonationController;
use App\Http\Controllers\API\SuperAdminImpersonationController;
use App\Http\Controllers\API\SuperAdminMonetizationController;
use App\Http\Controllers\API\SuperAdminReleaseController;
use App\Http\Controllers\API\SuperAdminReportController;
use App\Http\Controllers\API\SuratController;
use App\Http\Controllers\API\TeacherAchievementController;
use App\Http\Controllers\API\TeacherAchievementTypeController;
use App\Http\Controllers\API\TeacherChangeRequestController;
use App\Http\Controllers\API\TeacherDashboardController;
use App\Http\Controllers\API\TeacherMutationController;
use App\Http\Controllers\API\TeacherPointController;
use App\Http\Controllers\API\TeacherPointRewardController;
use App\Http\Controllers\API\TeacherRewardLogController;
use App\Http\Controllers\API\TeacherViolationController;
use App\Http\Controllers\API\TeacherViolationTypeController;
use App\Http\Controllers\API\TeachingJournalController;
use App\Http\Controllers\API\TemplateSuratController;
use App\Http\Controllers\API\UksMedicineController;
use App\Http\Controllers\API\UksReportController;
use App\Http\Controllers\API\UksVisitController;
use App\Http\Controllers\API\UksVisitTypeController;
use App\Http\Controllers\API\ViolationController;
use App\Http\Controllers\API\ViolationTypeController;
use App\Http\Controllers\API\WaliKelasController;
use Illuminate\Support\Facades\Route;

// API Info route
Route::get('/', function () {
    return response()->json([
        'message' => config('app.name') . ' API',
        'version' => '1.0.0',
        'endpoints' => [
            'public' => [
                'POST /api/v1/register' => 'Register new institution',
                'POST /api/v1/login' => 'Login user',
                'POST /api/v1/forgot-password' => 'Request password reset',
                'POST /api/v1/password-reset-requests' => 'Request school password reset via admin',
                'POST /api/v1/reset-password' => 'Reset password',
                'POST /api/v1/verify-email' => 'Verify email address',
                'POST /api/v1/resend-verification' => 'Resend verification email',
                'POST /api/v1/refresh-token' => 'Refresh access token',
            ],
            'protected' => [
                'POST /api/v1/logout' => 'Logout user',
                'GET /api/v1/me' => 'Get current user',
                'PUT /api/v1/me' => 'Update current user profile (name, email)',
                'PUT /api/v1/me/password' => 'Change current user password',
                'GET /api/v1/teacher/dashboard' => 'Teacher dashboard summary',
                'GET /api/v1/institution' => 'List institutions',
                'GET /api/v1/institution/my' => 'Get my institution',
                'GET /api/v1/institution/{id}' => 'Get institution detail',
                'POST /api/v1/institution' => 'Create institution',
                'PUT /api/v1/institution/{id}' => 'Update institution',
                'DELETE /api/v1/institution/{id}' => 'Delete institution',
                'GET /api/v1/student' => 'List students',
                'GET /api/v1/student/export' => 'Export students (full rows for Excel)',
                'GET /api/v1/student/{id}' => 'Get student detail',
                'POST /api/v1/student' => 'Create student',
                'PUT /api/v1/student/{id}' => 'Update student',
                'DELETE /api/v1/student/{id}' => 'Delete student',
                'GET /api/v1/employee' => 'List employees',
                'GET /api/v1/employee/export' => 'Export employees (full rows for Excel)',
                'GET /api/v1/employee/export/pdf' => 'Export employees PDF',
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
    Route::post('/password-reset-requests', [PasswordResetRequestController::class, 'store']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerificationEmail']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
});

// Public PPDB (tanpa auth): list periode & jalur, submit pendaftaran
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/public/ppdb/periods', [PublicPpdbController::class, 'openPeriods'])->name('public.ppdb.periods');
    Route::get('/public/ppdb/channels', [PublicPpdbController::class, 'openChannels'])->name('public.ppdb.channels');
});
Route::middleware('throttle:15,1')->get('/public/ppdb/prefill', [PublicPpdbController::class, 'prefill'])->name('public.ppdb.prefill');
Route::middleware('throttle:10,1')->get('/public/ppdb/check-result', [PublicPpdbController::class, 'checkResult'])->name('public.ppdb.check-result');
Route::middleware('throttle:20,1')->get('/public/ppdb/document-checklist', [PublicPpdbController::class, 'documentChecklist'])->name('public.ppdb.document-checklist');
Route::middleware('throttle:10,1')->get('/public/ppdb/registration-slip', [PublicPpdbController::class, 'registrationSlip'])->name('public.ppdb.registration-slip');
Route::middleware('throttle:10,1')->post('/public/ppdb/confirm-re-registration', [PublicPpdbController::class, 'confirmReRegistration'])->name('public.ppdb.confirm-re-registration');
Route::middleware('throttle:10,1')->post('/public/ppdb/documents', [PublicPpdbController::class, 'uploadDocument'])->name('public.ppdb.upload-document');
Route::middleware('throttle:10,1')->post('/public/ppdb/register', [PublicPpdbController::class, 'register'])->name('public.ppdb.register');

// Public exam attempt (siswa masuk ujian dengan token dari kartu peserta)
Route::middleware('throttle:60,1')->prefix('exam/attempt')->group(function () {
    Route::post('/enter', [ExamAttemptController::class, 'enter'])->name('exam.attempt.enter');
    Route::get('/question', [ExamAttemptController::class, 'question'])->name('exam.attempt.question');
    Route::put('/answer', [ExamAttemptController::class, 'saveAnswer'])->name('exam.attempt.answer');
    Route::post('/submit', [ExamAttemptController::class, 'submit'])->name('exam.attempt.submit');
});

// Public school landing: institusi by NPSN, buku tamu submit
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/public/school', [PublicSchoolController::class, 'showInstitution'])->name('public.school.show');
    Route::get('/public/school/posts', [SchoolPostController::class, 'publicByNpsn'])->name('public.school.posts');
});
Route::middleware('throttle:30,1')->get('/public/npsn-lookup', [PublicSchoolController::class, 'lookupNpsnReferensi'])->name('public.npsn-lookup');
Route::middleware('throttle:60,1')->prefix('public/regions')->group(function () {
    Route::get('/provinces', [RegionController::class, 'provinces'])->name('public.regions.provinces');
    Route::get('/regencies', [RegionController::class, 'regencies'])->name('public.regions.regencies');
    Route::get('/districts', [RegionController::class, 'districts'])->name('public.regions.districts');
    Route::get('/villages', [RegionController::class, 'villages'])->name('public.regions.villages');
});
Route::middleware('throttle:5,1')->post('/public/guest-visit', [PublicSchoolController::class, 'storeGuestVisit'])->name('public.guest-visit.store');

// Public perpustakaan digital (ebook publik by NPSN, tanpa login)
Route::middleware('throttle:60,1')->prefix('public/library')->group(function () {
    Route::get('ebooks', [PublicLibraryController::class, 'ebooks'])->name('public.library.ebooks');
    Route::get('ebooks/categories', [PublicLibraryController::class, 'categories'])->name('public.library.ebooks.categories');
    Route::get('books/{book}/viewer', [PublicLibraryController::class, 'issueViewer'])->name('public.library.books.viewer');
    Route::middleware('throttle:120,1')->get('books/{book}/ebook', [PublicLibraryController::class, 'streamEbook'])->name('public.library.books.ebook');
});

// Public landing stats & recent institutions (untuk halaman awal)
Route::middleware('throttle:30,1')->get('/public/stats', [PublicSchoolController::class, 'stats'])->name('public.stats');
Route::middleware('throttle:30,1')->get('/public/institutions/recent', [PublicSchoolController::class, 'recentInstitutions'])->name('public.institutions.recent');
Route::middleware('throttle:30,1')->get('/public/releases', [PublicReleaseController::class, 'index'])->name('public.releases.index');

// App branding (logo & favicon) - public, no auth. Tidak mengubah logo institusi.
Route::middleware('throttle:120,1')->get('/app-branding', [AppBrandingController::class, 'show'])->name('app-branding.show');

// Protected routes with rate limiting
Route::middleware(['auth:sanctum', 'throttle:60,1', 'institution.context', 'storage.quota'])->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::put('/me/password', [AuthController::class, 'changePassword']);
    Route::post('/me/switch-institution', [AuthController::class, 'switchInstitution']);
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::get('/permissions/teachers', [PermissionController::class, 'getTeachers']);
    Route::put('/permissions/users/{userId}', [PermissionController::class, 'updateUserPermissions']);
    Route::get('/permissions/institution-visibility', [PermissionController::class, 'getInstitutionVisibility']);
    Route::put('/permissions/institution-visibility', [PermissionController::class, 'updateInstitutionVisibility']);
    Route::get('/additional-duties', [AdditionalDutyController::class, 'index']);
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/filter-options', [AuditLogController::class, 'filterOptions'])->name('audit-logs.filter-options');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
    Route::get('/dashboard/charts', [DashboardChartsController::class, 'index'])->name('dashboard.charts');

    // Institution routes
    Route::middleware('module:institution')->group(function () {
        Route::get('/institution/my', [InstitutionController::class, 'myInstitution']);
        Route::put('/institution/{id}/active-academic-year', [InstitutionController::class, 'updateActiveAcademicYear'])->name('institution.update-active-academic-year');
        Route::post('/institution/{id}/logo', [InstitutionController::class, 'uploadLogo'])->name('institution.upload-logo');
        Route::post('/institution/{id}/cover-image', [InstitutionController::class, 'uploadCoverImage'])->name('institution.upload-cover-image');
        Route::get('/institution/{id}/logo', [InstitutionController::class, 'getLogo'])->name('institution.get-logo');
        // POST update agar body terbaca di hosting yang tidak meneruskan body PUT (nginx/shared hosting)
        Route::post('/institution/{id}/update', [InstitutionController::class, 'update'])->name('institution.update-post');
        Route::apiResource('institution', InstitutionController::class);
    });

    // Student self-service profile (must be registered before /student/{id})
    Route::get('/student/profile', [StudentChangeRequestController::class, 'showMyProfile'])->name('student.profile.show');
    Route::put('/student/profile', [StudentChangeRequestController::class, 'updateMyProfile'])->name('student.profile.update');

    // Student routes (list tanpa cache agar tambah/edit/import langsung muncul)
    Route::middleware('module:student')->group(function () {
        Route::get('/student', [StudentController::class, 'index']);
        Route::get('/student/export', [StudentController::class, 'export'])->name('student.export');
        Route::get('/student/account-status', [StudentController::class, 'accountStatus'])->name('student.account-status');
        Route::get('/student/nis-numbering', [StudentNisController::class, 'show'])->name('student.nis-numbering.show');
        Route::put('/student/nis-numbering', [StudentNisController::class, 'update'])->name('student.nis-numbering.update');
        Route::post('/student/nis-numbering', [StudentNisController::class, 'update'])->name('student.nis-numbering.update-post');
        Route::post('/student/generate-nis/preview', [StudentNisController::class, 'previewGenerate'])->name('student.generate-nis.preview');
        Route::post('/student/generate-nis', [StudentNisController::class, 'generateBulk'])->name('student.generate-nis.bulk');
        Route::post('/student', [StudentController::class, 'store']);
        Route::get('/student/feeder-alumni', [StudentController::class, 'feederAlumni'])->name('student.feeder-alumni');
        Route::post('/student/pull-from-feeder', [StudentController::class, 'pullFromFeeder'])->name('student.pull-from-feeder');
        Route::post('/student/promote', [StudentController::class, 'promote'])->name('student.promote');
        Route::post('/student/import', [StudentController::class, 'import'])->name('student.import');
        Route::post('/student/ensure-accounts-bulk', [StudentController::class, 'ensureAccountsBulk'])->name('student.ensure-accounts-bulk');
        Route::post('/student/{id}/generate-nis', [StudentNisController::class, 'generateOne'])->name('student.generate-nis.one');
        Route::post('/student/graduate-bulk', [AlumniController::class, 'graduateBulk']);
        Route::post('/student/revoke-graduation-bulk', [AlumniController::class, 'revokeGraduationBulk']);
        Route::get('/alumni', [AlumniController::class, 'index']);
        Route::get('/alumni/graduation-years', [AlumniController::class, 'graduationYears']);
        Route::get('/student/{id}', [StudentController::class, 'show']);
        Route::get('/student/{id}/buku-induk', [BukuIndukController::class, 'show'])->name('student.buku-induk');
        Route::get('/student/{id}/buku-induk/pdf', [BukuIndukController::class, 'print'])->name('student.buku-induk.pdf');
        Route::get('/student/{id}/biodata/pdf', [StudentController::class, 'printBiodata'])->name('student.biodata.pdf');
        Route::put('/student/{id}', [StudentController::class, 'update']);
        Route::post('/student/{id}/ensure-account', [StudentController::class, 'ensureAccount'])->name('student.ensure-account');
        Route::post('/student/{id}/reset-password', [StudentController::class, 'resetPassword'])->name('student.reset-password');
        Route::delete('/student/{id}', [StudentController::class, 'destroy']);
        Route::post('/student/{id}/restore', [StudentController::class, 'restore']);
        Route::delete('/student/{id}/force', [StudentController::class, 'forceDestroy']);
        Route::post('/student/{id}/graduate', [AlumniController::class, 'graduate']);
        Route::post('/student/{id}/revoke-graduation', [AlumniController::class, 'revokeGraduation']);
        Route::post('/student/{id}/documents', [StudentController::class, 'uploadDocument'])->name('student.upload-document');
        Route::delete('/student/{id}/documents/{documentId}', [StudentController::class, 'deleteDocument'])->name('student.delete-document');
        Route::get('/student/{id}/documents/{documentId}/download', [StudentController::class, 'downloadDocument'])->name('student.download-document');
        Route::post('/student/{id}/photo', [StudentController::class, 'uploadPhoto'])->name('student.upload-photo');
        Route::delete('/student/{id}/photo', [StudentController::class, 'deletePhoto'])->name('student.delete-photo');
        // Tracking destinasi alumni (lanjut sekolah/kuliah/kerja/dll)
        Route::get('/alumni/destination-types', [AlumniDestinationController::class, 'types'])->name('alumni.destination-types');
        Route::get('/alumni/students/{studentId}/destinations', [AlumniDestinationController::class, 'indexByStudent'])->name('alumni.destinations.by-student');
        Route::post('/alumni-destinations', [AlumniDestinationController::class, 'store'])->name('alumni-destinations.store');
        Route::put('/alumni-destinations/{alumni_destination}', [AlumniDestinationController::class, 'update'])->name('alumni-destinations.update');
        Route::post('/alumni-destinations/{alumni_destination}/approve', [AlumniDestinationController::class, 'approve'])->name('alumni-destinations.approve');
        Route::post('/alumni-destinations/{alumni_destination}/reject', [AlumniDestinationController::class, 'reject'])->name('alumni-destinations.reject');
        Route::delete('/alumni-destinations/{alumni_destination}', [AlumniDestinationController::class, 'destroy'])->name('alumni-destinations.destroy');
        // Student mutation (mutasi siswa)
        Route::get('/student-mutations/target-institutions', [StudentMutationController::class, 'searchTargetInstitutions'])->name('student-mutations.target-institutions');
        Route::get('/student-mutations/origin-institutions', [StudentMutationController::class, 'searchOriginInstitutions'])->name('student-mutations.origin-institutions');
        Route::get('/student-mutations/lookup-student', [StudentMutationController::class, 'lookupStudent'])->name('student-mutations.lookup-student');
        Route::get('/student-mutations/lookup-student-at-origin', [StudentMutationController::class, 'lookupStudentAtOrigin'])->name('student-mutations.lookup-student-at-origin');
        Route::post('/student-mutations/pull', [StudentMutationController::class, 'pull'])->name('student-mutations.pull');
        Route::post('/student-mutations/{student_mutation}/approve', [StudentMutationController::class, 'approve'])->name('student-mutations.approve');
        Route::post('/student-mutations/{student_mutation}/cancel', [StudentMutationController::class, 'cancel'])->name('student-mutations.cancel');
        Route::post('/student-mutations/{student_mutation}/cancel-decision', [StudentMutationController::class, 'decideCancel'])->name('student-mutations.cancel-decision');
        Route::get('/student-mutations', [StudentMutationController::class, 'index']);
        Route::get('/student-mutations/report', [StudentMutationController::class, 'report'])->name('student-mutations.report');
        Route::get('/student-mutations/export', [StudentMutationController::class, 'export'])->name('student-mutations.export');
        Route::get('/student-mutations/by-student/{student_id}', [StudentMutationController::class, 'historyByStudent'])->name('student-mutations.by-student');
        Route::get('/student-mutations/history-by-nik', [StudentMutationController::class, 'historyByNik'])->name('student-mutations.history-by-nik');
        Route::post('/student-mutations', [StudentMutationController::class, 'store']);
        Route::get('/student-mutations/{student_mutation}', [StudentMutationController::class, 'show']);
    });

    // Violation routes (Pelanggaran)
    Route::middleware('module:violation')->group(function () {
        Route::get('/violations', [ViolationController::class, 'index']);
        Route::post('/violations', [ViolationController::class, 'store']);
        Route::get('/violations/by-student/{studentId}', [ViolationController::class, 'byStudent'])->name('violations.by-student');
        Route::get('/violations/classes-lite', [ViolationController::class, 'classesLite']);
        Route::get('/violations/students-lite', [ViolationController::class, 'studentsLite']);
        Route::post('/violations/{violation}/approve', [ViolationController::class, 'approve']);
        Route::post('/violations/{violation}/reject', [ViolationController::class, 'reject']);
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
        Route::post('/achievements/{achievement}/approve', [AchievementController::class, 'approve']);
        Route::post('/achievements/{achievement}/reject', [AchievementController::class, 'reject']);
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
        Route::get('/counseling/classes-lite', [CounselingController::class, 'classesLite'])->name('counseling.classes-lite');
        Route::get('/counseling/students-lite', [CounselingController::class, 'studentsLite'])->name('counseling.students-lite');
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

    // Laporan BK (akses jika punya modul violation, counseling, atau bk_report untuk wali kelas)
    Route::middleware('module:violation|counseling|bk_report')->group(function () {
        Route::get('/bk-reports/summary', [BkReportController::class, 'summary'])->name('bk-reports.summary');
        Route::get('/bk-reports/violations', [BkReportController::class, 'violationDetail'])->name('bk-reports.violations');
        Route::get('/bk-reports/export', [BkReportController::class, 'export'])->name('bk-reports.export');
        Route::get('/bk-reports/export-violations', [BkReportController::class, 'exportViolations'])->name('bk-reports.export-violations');
    });

    // Portal siswa: ringkasan kunjungan UKS sendiri (harus sebelum /uks/visits/{id})
    Route::get('/uks/visits/my', [UksVisitController::class, 'my'])->name('uks.visits.my');

    // Portal guru/staff: cuti sendiri (tanpa modul kepegawaian penuh)
    Route::get('/employee-leaves/meta', [EmployeeLeaveController::class, 'meta'])->name('employee-leaves.meta');
    Route::get('/employee-leaves/my', [EmployeeLeaveController::class, 'my'])->name('employee-leaves.my');
    Route::post('/employee-leaves/my', [EmployeeLeaveController::class, 'storeMy'])->name('employee-leaves.my.store');
    Route::post('/employee-leaves/{employee_leave_request}/cancel', [EmployeeLeaveController::class, 'cancel'])->name('employee-leaves.cancel');

    // Kepegawaian lanjutan (cuti, SK, jabatan struktural, riwayat)
    Route::middleware('module:kepegawaian')->group(function () {
        Route::get('/employee-leaves', [EmployeeLeaveController::class, 'index']);
        Route::post('/employee-leaves', [EmployeeLeaveController::class, 'store']);
        Route::get('/employee-leaves/{employee_leave_request}', [EmployeeLeaveController::class, 'show']);
        Route::post('/employee-leaves/{employee_leave_request}/decide', [EmployeeLeaveController::class, 'decide']);

        Route::get('/employee-decrees/meta', [EmployeeDecreeController::class, 'meta']);
        Route::get('/employee-decrees', [EmployeeDecreeController::class, 'index']);
        Route::post('/employee-decrees', [EmployeeDecreeController::class, 'store']);
        Route::get('/employee-decrees/{employee_decree}', [EmployeeDecreeController::class, 'show']);
        Route::match(['put', 'post'], '/employee-decrees/{employee_decree}', [EmployeeDecreeController::class, 'update']);
        Route::delete('/employee-decrees/{employee_decree}', [EmployeeDecreeController::class, 'destroy']);
        Route::get('/employee-decrees/{employee_decree}/download', [EmployeeDecreeController::class, 'download']);

        Route::get('/structural-positions', [EmployeeStructuralPositionController::class, 'positions']);
        Route::get('/employee-structural-positions', [EmployeeStructuralPositionController::class, 'index']);
        Route::post('/employee-structural-positions', [EmployeeStructuralPositionController::class, 'store']);
        Route::get('/employee-structural-positions/{employee_structural_position}', [EmployeeStructuralPositionController::class, 'show']);
        Route::post('/employee-structural-positions/{employee_structural_position}/end', [EmployeeStructuralPositionController::class, 'end']);

        Route::get('/employee-career-history/{employeeId}', [EmployeeCareerHistoryController::class, 'show']);
    });

    // UKS (Usaha Kesehatan Sekolah)
    Route::middleware('module:uks')->group(function () {
        Route::get('/uks/visits', [UksVisitController::class, 'index']);
        Route::get('/uks/visits/stats', [UksVisitController::class, 'stats'])->name('uks.visits.stats');
        Route::get('/uks/visits/export', [UksVisitController::class, 'export'])->name('uks.visits.export');
        Route::get('/uks/visits/recorders', [UksVisitController::class, 'recorders'])->name('uks.visits.recorders');
        Route::get('/uks/visits/classes-lite', [UksVisitController::class, 'classesLite'])->name('uks.visits.classes-lite');
        Route::get('/uks/visits/students-lite', [UksVisitController::class, 'studentsLite'])->name('uks.visits.students-lite');
        Route::post('/uks/visits', [UksVisitController::class, 'store']);
        Route::get('/uks/visits/by-student/{studentId}', [UksVisitController::class, 'byStudent'])->name('uks.visits.by-student');
        Route::get('/uks/visits/{uks_visit}', [UksVisitController::class, 'show']);
        Route::put('/uks/visits/{uks_visit}', [UksVisitController::class, 'update']);
        Route::delete('/uks/visits/{uks_visit}', [UksVisitController::class, 'destroy']);

        Route::get('/uks/visit-types', [UksVisitTypeController::class, 'index']);
        Route::post('/uks/visit-types', [UksVisitTypeController::class, 'store']);
        Route::post('/uks/visit-types/seed-defaults', [UksVisitTypeController::class, 'seedDefaults'])->name('uks.visit-types.seed');
        Route::get('/uks/visit-types/{uks_visit_type}', [UksVisitTypeController::class, 'show']);
        Route::put('/uks/visit-types/{uks_visit_type}', [UksVisitTypeController::class, 'update']);
        Route::delete('/uks/visit-types/{uks_visit_type}', [UksVisitTypeController::class, 'destroy']);

        Route::get('/uks-reports/summary', [UksReportController::class, 'summary'])->name('uks-reports.summary');
        Route::get('/uks-reports/visits', [UksReportController::class, 'visits'])->name('uks-reports.visits');
        Route::get('/uks-reports/export', [UksReportController::class, 'export'])->name('uks-reports.export');
        Route::get('/uks-reports/export-visits', [UksReportController::class, 'exportVisits'])->name('uks-reports.export-visits');
        Route::get('/uks-reports/export-pdf', [UksReportController::class, 'exportPdf'])->name('uks-reports.export-pdf');

        // Inventaris obat / stok UKS
        Route::get('/uks/medicines/summary', [UksMedicineController::class, 'summary'])->name('uks.medicines.summary');
        Route::get('/uks/medicines/transactions', [UksMedicineController::class, 'transactions'])->name('uks.medicines.transactions');
        Route::post('/uks/medicines/transactions', [UksMedicineController::class, 'storeTransaction'])->name('uks.medicines.transactions.store');
        Route::get('/uks/medicines', [UksMedicineController::class, 'index']);
        Route::post('/uks/medicines', [UksMedicineController::class, 'store']);
        Route::get('/uks/medicines/{uks_medicine}', [UksMedicineController::class, 'show']);
        Route::put('/uks/medicines/{uks_medicine}', [UksMedicineController::class, 'update']);
        Route::delete('/uks/medicines/{uks_medicine}', [UksMedicineController::class, 'destroy']);
    });

    // Apresiasi Guru (poin & prestasi guru)
    Route::middleware('module:teacher_appreciation')->group(function () {
        Route::get('/teacher-achievement-types', [TeacherAchievementTypeController::class, 'index']);
        Route::post('/teacher-achievement-types', [TeacherAchievementTypeController::class, 'store']);
        Route::get('/teacher-achievement-types/{teacher_achievement_type}', [TeacherAchievementTypeController::class, 'show']);
        Route::put('/teacher-achievement-types/{teacher_achievement_type}', [TeacherAchievementTypeController::class, 'update']);
        Route::delete('/teacher-achievement-types/{teacher_achievement_type}', [TeacherAchievementTypeController::class, 'destroy']);

        Route::get('/teacher-achievements', [TeacherAchievementController::class, 'index']);
        Route::post('/teacher-achievements', [TeacherAchievementController::class, 'store']);
        Route::get('/teacher-achievements/by-employee/{employeeId}', [TeacherAchievementController::class, 'byEmployee']);
        Route::post('/teacher-achievements/{teacher_achievement}/approve', [TeacherAchievementController::class, 'approve']);
        Route::post('/teacher-achievements/{teacher_achievement}/reject', [TeacherAchievementController::class, 'reject']);
        Route::get('/teacher-achievements/{teacher_achievement}', [TeacherAchievementController::class, 'show']);
        Route::match(['put', 'post'], '/teacher-achievements/{teacher_achievement}', [TeacherAchievementController::class, 'update']);
        Route::delete('/teacher-achievements/{teacher_achievement}', [TeacherAchievementController::class, 'destroy']);

        Route::get('/teacher-point-rewards', [TeacherPointRewardController::class, 'index']);
        Route::post('/teacher-point-rewards', [TeacherPointRewardController::class, 'store']);
        Route::get('/teacher-point-rewards/{teacher_point_reward}', [TeacherPointRewardController::class, 'show']);
        Route::put('/teacher-point-rewards/{teacher_point_reward}', [TeacherPointRewardController::class, 'update']);
        Route::delete('/teacher-point-rewards/{teacher_point_reward}', [TeacherPointRewardController::class, 'destroy']);

        Route::get('/teacher-reward-logs', [TeacherRewardLogController::class, 'index']);
        Route::post('/teacher-reward-logs', [TeacherRewardLogController::class, 'store']);
        Route::get('/teacher-reward-logs/by-employee/{employeeId}', [TeacherRewardLogController::class, 'byEmployee']);
        Route::delete('/teacher-reward-logs/{teacher_reward_log}', [TeacherRewardLogController::class, 'destroy']);

        Route::get('/teacher-points', [TeacherPointController::class, 'index']);
        Route::get('/teacher-points/leaderboard', [TeacherPointController::class, 'leaderboard']);
        Route::get('/teacher-points/report-summary', [TeacherPointController::class, 'reportSummary']);
        Route::get('/teacher-points/{employeeId}/summary', [TeacherPointController::class, 'summary']);
        Route::get('/teacher-appreciation/employees', [TeacherPointController::class, 'employees']);

        // Jenis pelanggaran & approve (KS/admin)
        Route::get('/teacher-violation-types', [TeacherViolationTypeController::class, 'index']);
        Route::post('/teacher-violation-types', [TeacherViolationTypeController::class, 'store']);
        Route::get('/teacher-violation-types/{teacher_violation_type}', [TeacherViolationTypeController::class, 'show']);
        Route::put('/teacher-violation-types/{teacher_violation_type}', [TeacherViolationTypeController::class, 'update']);
        Route::delete('/teacher-violation-types/{teacher_violation_type}', [TeacherViolationTypeController::class, 'destroy']);

        Route::post('/teacher-violations/{teacher_violation}/approve', [TeacherViolationController::class, 'approve']);
        Route::post('/teacher-violations/{teacher_violation}/reject', [TeacherViolationController::class, 'reject']);
        Route::match(['put', 'post'], '/teacher-violations/{teacher_violation}', [TeacherViolationController::class, 'update']);
        Route::delete('/teacher-violations/{teacher_violation}', [TeacherViolationController::class, 'destroy']);
    });

    // Catat/lihat pelanggaran: KS (teacher_appreciation) atau Guru Piket (teacher_violation_report)
    Route::middleware('module:teacher_appreciation|teacher_violation_report')->group(function () {
        Route::get('/teacher-violations', [TeacherViolationController::class, 'index']);
        Route::post('/teacher-violations', [TeacherViolationController::class, 'store']);
        Route::get('/teacher-violations/{teacher_violation}', [TeacherViolationController::class, 'show']);
        Route::get('/teacher-violation-types-active', [TeacherViolationTypeController::class, 'index']);
        Route::get('/teacher-appreciation/employees-lite', [TeacherPointController::class, 'employees']);
        Route::get('/teacher-appreciation/pending-counts', [TeacherPointController::class, 'pendingCounts']);
        Route::get('/teacher-appreciation/bootstrap', [TeacherPointController::class, 'bootstrap']);
    });

    // Modul Guru Piket: jadwal, log harian, monitoring, laporan mingguan
    Route::middleware('module:guru_piket|guru_piket_manage')->group(function () {
        Route::get('/piket/dashboard', [PiketController::class, 'dashboard']);
        Route::get('/piket/today', [PiketController::class, 'today']);
        Route::get('/piket/settings', [PiketController::class, 'settings']);
        Route::put('/piket/settings', [PiketController::class, 'updateSettings']);
        Route::get('/piket/employees-lite', [PiketController::class, 'employeesLite']);
        Route::get('/piket/students-lite', [PiketController::class, 'studentsLite']);
        Route::get('/piket/classes-lite', [PiketController::class, 'classesLite']);

        Route::get('/piket-schedules', [PiketController::class, 'schedulesIndex']);
        Route::post('/piket-schedules', [PiketController::class, 'schedulesStore']);
        Route::put('/piket-schedules/{piket_schedule}', [PiketController::class, 'schedulesUpdate']);
        Route::delete('/piket-schedules/{piket_schedule}', [PiketController::class, 'schedulesDestroy']);

        Route::get('/piket-logs', [PiketController::class, 'logsIndex']);
        Route::post('/piket-logs', [PiketController::class, 'logsStore']);
        Route::put('/piket-logs/{piket_log}', [PiketController::class, 'logsUpdate']);
        Route::post('/piket-logs/{piket_log}/review', [PiketController::class, 'logsReview']);
        Route::delete('/piket-logs/{piket_log}', [PiketController::class, 'logsDestroy']);

        Route::get('/piket-incidents', [PiketController::class, 'incidentsIndex']);
        Route::post('/piket-incidents', [PiketController::class, 'incidentsStore']);
        Route::put('/piket-incidents/{piket_incident}', [PiketController::class, 'incidentsUpdate']);
        Route::delete('/piket-incidents/{piket_incident}', [PiketController::class, 'incidentsDestroy']);
        Route::post('/piket-incidents/{piket_incident}/propose-violation', [PiketController::class, 'proposeViolation']);
        Route::post('/piket-incidents/{piket_incident}/propose-teacher-violation', [PiketController::class, 'proposeTeacherViolation']);
        Route::get('/piket/violation-types-active', [PiketController::class, 'violationTypesActive']);
        Route::get('/piket/teacher-violation-types-active', [PiketController::class, 'teacherViolationTypesActive']);
        Route::post('/piket/scan/empty-classes', [PiketController::class, 'scanEmptyClasses']);
        Route::post('/piket/scan/teacher-lateness', [PiketController::class, 'scanTeacherLateness']);
        Route::post('/piket/scan', [PiketController::class, 'scanAll']);

        Route::get('/piket/report', [PiketController::class, 'report']);
        Route::get('/piket/weekly-report', [PiketController::class, 'weeklyReport']);
        Route::get('/piket/report-pdf', [PiketController::class, 'weeklyReport']);
    });

    // Self-service apresiasi guru (tanpa wajib modul)
    Route::get('/teacher/my-points/summary', [MyTeacherAppreciationController::class, 'summary']);
    Route::get('/teacher/my-points/leaderboard', [MyTeacherAppreciationController::class, 'leaderboard']);
    Route::get('/teacher/my-achievements', [MyTeacherAppreciationController::class, 'achievements']);
    Route::post('/teacher/my-achievements', [MyTeacherAppreciationController::class, 'submit']);
    Route::get('/teacher/my-achievements/{teacher_achievement}', [MyTeacherAppreciationController::class, 'show']);
    Route::get('/teacher/my-achievement-types', [MyTeacherAppreciationController::class, 'types']);
    Route::get('/teacher/my-violations', [MyTeacherAppreciationController::class, 'violations']);

    // Portal pegawai: slip gaji sendiri (tanpa module:payroll)
    Route::prefix('payroll/my')->group(function () {
        Route::get('slips', [EmployeePayrollPortalController::class, 'slips'])->name('payroll.my.slips');
        Route::get('slips/{slip}', [EmployeePayrollPortalController::class, 'show'])->name('payroll.my.slips.show');
        Route::get('slips/{slip}/pdf', [EmployeePayrollPortalController::class, 'pdf'])->name('payroll.my.slips.pdf');
    });

    // Jurnal Mengajar (Teaching Journal) + Absensi Siswa per jam
    Route::middleware('module:teaching_journal')->group(function () {
        Route::get('/teaching-journals/export', [TeachingJournalController::class, 'export'])->name('teaching-journals.export');
        Route::get('/teaching-journals', [TeachingJournalController::class, 'index'])->name('teaching-journals.index');
        Route::post('/teaching-journals', [TeachingJournalController::class, 'store'])->name('teaching-journals.store');
        Route::get('/teaching-journals/{teaching_journal}', [TeachingJournalController::class, 'show'])->name('teaching-journals.show');
        Route::put('/teaching-journals/{teaching_journal}', [TeachingJournalController::class, 'update'])->name('teaching-journals.update');
        Route::delete('/teaching-journals/{teaching_journal}', [TeachingJournalController::class, 'destroy'])->name('teaching-journals.destroy');
        Route::get('/teaching-journals/{teachingJournalId}/attendances', [StudentAttendanceController::class, 'index'])->name('teaching-journals.attendances.index');
        Route::post('/teaching-journals/attendances', [StudentAttendanceController::class, 'store'])->name('teaching-journals.attendances.store');
        Route::get('/student-attendances/prepare-from-schedule', [StudentAttendanceController::class, 'prepareFromSchedule'])->name('student-attendances.prepare-from-schedule');
        Route::post('/student-attendances/from-schedule', [StudentAttendanceController::class, 'storeFromSchedule'])->name('student-attendances.from-schedule');
        Route::put('/student-attendances/{student_attendance}', [StudentAttendanceController::class, 'update'])->name('student-attendances.update');
        Route::get('/student-attendances/rekap', [StudentAttendanceController::class, 'rekap'])->name('student-attendances.rekap');
        Route::get('/student-attendances/export-rekap', [StudentAttendanceController::class, 'exportRekap'])->name('student-attendances.export-rekap');
    });

    // Student attendance self history (for portal siswa)
    Route::get('/student-attendances/my', [StudentAttendanceController::class, 'my'])->name('student-attendances.my');
    Route::get('/student-attendances/my/export', [StudentAttendanceController::class, 'exportMy'])->name('student-attendances.my.export');

    // Portal siswa: tagihan & riwayat pembayaran sendiri (tanpa module:finance)
    Route::prefix('finance/my')->group(function () {
        Route::get('summary', [StudentFinanceController::class, 'summary'])->name('finance.my.summary');
        Route::get('invoices', [StudentFinanceController::class, 'invoices'])->name('finance.my.invoices');
        Route::get('payments', [StudentFinanceController::class, 'payments'])->name('finance.my.payments');
        Route::get('payments/{payment}/receipt', [StudentFinanceController::class, 'receipt'])->name('finance.my.payments.receipt');
    });

    // Portal siswa: penempatan & jurnal PKL (tanpa module:pkl) — SMK/MAK
    Route::middleware('vocational')->prefix('pkl/my')->group(function () {
        Route::get('placements', [PklJournalController::class, 'myPlacements'])->name('pkl.my.placements');
        Route::get('placements/{pkl_placement}', [PklJournalController::class, 'myPlacementShow'])->name('pkl.my.placements.show');
        Route::get('placements/{pkl_placement}/journals', [PklJournalController::class, 'myJournalsIndex'])->name('pkl.my.journals.index');
        Route::post('placements/{pkl_placement}/journals', [PklJournalController::class, 'myJournalsStore'])->name('pkl.my.journals.store');
        Route::put('placements/{pkl_placement}/journals/{journal}', [PklJournalController::class, 'myJournalsUpdate'])->name('pkl.my.journals.update');
        Route::delete('placements/{pkl_placement}/journals/{journal}', [PklJournalController::class, 'myJournalsDestroy'])->name('pkl.my.journals.destroy');
    });

    // Portal siswa/alumni: lowongan & lamaran BKK (tanpa module:bkk) — SMK/MAK
    Route::middleware('vocational')->prefix('bkk/my')->group(function () {
        Route::get('vacancies', [BkkVacancyController::class, 'myOpen'])->name('bkk.my.vacancies');
        Route::get('applications', [BkkApplicationController::class, 'myIndex'])->name('bkk.my.applications');
        Route::post('applications', [BkkApplicationController::class, 'myStore'])->name('bkk.my.applications.store');
    });

    // Absensi (Guru & Staff per hari)
    Route::middleware('module:attendance')->group(function () {
        Route::get('/employee-attendances/status-options', [EmployeeAttendanceController::class, 'statusOptions'])->name('employee-attendances.status-options');
        Route::get('/employee-attendances/rekap', [EmployeeAttendanceController::class, 'rekap'])->name('employee-attendances.rekap');
        Route::get('/employee-attendances/export-rekap', [EmployeeAttendanceController::class, 'exportRekap'])->name('employee-attendances.export-rekap');
        Route::post('/employee-attendances/bulk', [EmployeeAttendanceController::class, 'bulkStore'])->name('employee-attendances.bulk');
        Route::get('/employee-attendances', [EmployeeAttendanceController::class, 'index'])->name('employee-attendances.index');
        Route::post('/employee-attendances', [EmployeeAttendanceController::class, 'store'])->name('employee-attendances.store');
        Route::put('/employee-attendances/{employee_attendance}', [EmployeeAttendanceController::class, 'update'])->name('employee-attendances.update');
    });

    // QR Code Attendance: generate/cetak kartu (TU/admin), scan juga untuk guru mapel
    Route::middleware('module:attendance')->group(function () {
        Route::get('/qr-attendance/student/{student}/generate', [QrAttendanceController::class, 'generateStudentQr'])->name('qr-attendance.student.generate');
        Route::get('/qr-attendance/employee/{employee}/generate', [QrAttendanceController::class, 'generateEmployeeQr'])->name('qr-attendance.employee.generate');
        Route::post('/qr-attendance/students/generate-bulk', [QrAttendanceController::class, 'generateStudentBulk'])->name('qr-attendance.student.generate-bulk');
        Route::post('/qr-attendance/employees/generate-bulk', [QrAttendanceController::class, 'generateEmployeeBulk'])->name('qr-attendance.employee.generate-bulk');
        Route::get('/qr-attendance/students/print-pdf', [QrAttendanceController::class, 'printStudentPdf'])->name('qr-attendance.student.print-pdf');
        Route::get('/qr-attendance/employees/print-pdf', [QrAttendanceController::class, 'printEmployeePdf'])->name('qr-attendance.employee.print-pdf');
    });
    Route::middleware('module:attendance|teaching_journal')->group(function () {
        Route::get('/qr-attendance/location-config', [QrAttendanceController::class, 'locationConfig'])->name('qr-attendance.location-config');
        Route::post('/qr-attendance/scan', [QrAttendanceController::class, 'scanQrAttendance'])->name('qr-attendance.scan');
    });

    Route::middleware('throttle:30,1')->get('/geocode/search', [GeocodeController::class, 'search'])->name('geocode.search');

    // Buku Nilai (Grade Book)
    Route::middleware('module:grade_book')->group(function () {
        Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
        Route::get('/grades/by-class-subject-semester', [GradeController::class, 'getByClassSubjectSemester'])->name('grades.by-class-subject-semester');
        Route::get('/grades/by-class-semester', [GradeController::class, 'getByClassSemester'])->name('grades.by-class-semester');
        Route::get('/grades/by-student-semester', [GradeController::class, 'getByStudentSemester'])->name('grades.by-student-semester');
        Route::get('/grades/completeness', [GradeController::class, 'completeness'])->name('grades.completeness');
        Route::get('/grades/export', [GradeController::class, 'export'])->name('grades.export');
        Route::get('/grades/export-pdf', [GradeController::class, 'exportPdf'])->name('grades.export-pdf');
        Route::get('/grades/export-student-raport', [GradeController::class, 'exportStudentRaport'])->name('grades.export-student-raport');
        Route::get('/grades/export-class-raport', [GradeController::class, 'exportClassRaport'])->name('grades.export-class-raport');
        Route::get('/grades/export-class-raport-pdf', [GradeController::class, 'exportClassRaportPdf'])->name('grades.export-class-raport-pdf');
        Route::post('/grades/bulk', [GradeController::class, 'bulkUpsert'])->name('grades.bulk');
        Route::post('/grades/kkm', [GradeController::class, 'upsertSubjectKkm'])->name('grades.kkm.upsert');
        Route::post('/grades/weights', [GradeController::class, 'upsertGradeWeight'])->name('grades.weights.upsert');
        Route::get('/grades/remedials/below-kkm', [GradeRemedialController::class, 'belowKkm'])->name('grades.remedials.below-kkm');
        Route::get('/grades/remedials', [GradeRemedialController::class, 'index'])->name('grades.remedials.index');
        Route::post('/grades/remedials', [GradeRemedialController::class, 'store'])->name('grades.remedials.store');
        Route::post('/grades/remedials/{gradeRemedial}/complete', [GradeRemedialController::class, 'complete'])->name('grades.remedials.complete');
        Route::post('/grades/remedials/{gradeRemedial}/cancel', [GradeRemedialController::class, 'cancel'])->name('grades.remedials.cancel');
        Route::post('/grades', [GradeController::class, 'store'])->name('grades.store');
        Route::get('/grades/{grade}', [GradeController::class, 'show'])->name('grades.show');
        Route::put('/grades/{grade}', [GradeController::class, 'update'])->name('grades.update');
        Route::delete('/grades/{grade}', [GradeController::class, 'destroy'])->name('grades.destroy');
    });

    // Employee routes (tanpa cache list agar tambah/edit/hapus selalu dapat data segar)
    Route::middleware('module:teacher')->group(function () {
        Route::get('/teacher-mutations/target-institutions', [TeacherMutationController::class, 'searchTargetInstitutions'])->name('teacher-mutations.target-institutions');
        Route::get('/teacher-mutations/origin-institutions', [TeacherMutationController::class, 'searchOriginInstitutions'])->name('teacher-mutations.origin-institutions');
        Route::get('/teacher-mutations/lookup-teacher', [TeacherMutationController::class, 'lookupTeacher'])->name('teacher-mutations.lookup-teacher');
        Route::get('/teacher-mutations/lookup-teacher-at-origin', [TeacherMutationController::class, 'lookupTeacherAtOrigin'])->name('teacher-mutations.lookup-teacher-at-origin');
        Route::post('/teacher-mutations/pull', [TeacherMutationController::class, 'pull'])->name('teacher-mutations.pull');
        Route::post('/teacher-mutations/{teacher_mutation}/approve', [TeacherMutationController::class, 'approve'])->name('teacher-mutations.approve');
        Route::post('/teacher-mutations/{teacher_mutation}/cancel', [TeacherMutationController::class, 'cancel'])->name('teacher-mutations.cancel');
        Route::post('/teacher-mutations/{teacher_mutation}/cancel-decision', [TeacherMutationController::class, 'decideCancel'])->name('teacher-mutations.cancel-decision');
        Route::get('/teacher-mutations', [TeacherMutationController::class, 'index']);
        Route::get('/teacher-mutations/report', [TeacherMutationController::class, 'report'])->name('teacher-mutations.report');
        Route::get('/teacher-mutations/export', [TeacherMutationController::class, 'export'])->name('teacher-mutations.export');
        Route::get('/teacher-mutations/by-employee/{employee_id}', [TeacherMutationController::class, 'historyByEmployee'])->name('teacher-mutations.by-employee');
        // Identitas guru untuk riwayat mutasi: NIK (banyak guru tidak punya NUPTK).
        Route::get('/teacher-mutations/history-by-nik', [TeacherMutationController::class, 'historyByNik'])->name('teacher-mutations.history-by-nik');
        Route::post('/teacher-mutations', [TeacherMutationController::class, 'store']);
        Route::get('/teacher-mutations/{teacher_mutation}', [TeacherMutationController::class, 'show']);

        Route::get('/employee', [EmployeeController::class, 'index']);
        Route::get('/employee/search', [EmployeeController::class, 'searchByNik']);
        Route::get('/employee/export', [EmployeeController::class, 'export'])->name('employee.export');
        Route::get('/employee/export/pdf', [EmployeeController::class, 'exportPdf'])->name('employee.export.pdf');
        Route::get('/employee/{id}', [EmployeeController::class, 'show']);
        Route::post('/employee', [EmployeeController::class, 'store']);
        Route::put('/employee/{id}', [EmployeeController::class, 'update']);
        Route::delete('/employee/{id}', [EmployeeController::class, 'destroy']);
        Route::post('/employee/{id}/restore', [EmployeeController::class, 'restore']);
        Route::delete('/employee/{id}/force', [EmployeeController::class, 'forceDestroy']);
        Route::post('/employee/{id}/reset-password', [EmployeeController::class, 'resetPasswordByAdmin'])->name('employee.reset-password');
        Route::post('/employee/{id}/impersonate', [InstitutionImpersonationController::class, 'start'])->name('employee.impersonate');
        Route::post('/employee/import', [EmployeeController::class, 'import'])->name('employee.import');
        Route::post('/employee/{id}/documents', [EmployeeController::class, 'uploadDocument'])->name('employee.upload-document');
        Route::delete('/employee/{id}/documents/{documentId}', [EmployeeController::class, 'deleteDocument'])->name('employee.delete-document');
        Route::get('/employee/{id}/documents/{documentId}/download', [EmployeeController::class, 'downloadDocument'])->name('employee.download-document');
        Route::post('/employee/{id}/photo', [EmployeeController::class, 'uploadPhoto'])->name('employee.upload-photo');
        Route::delete('/employee/{id}/photo', [EmployeeController::class, 'deletePhoto'])->name('employee.delete-photo');
        Route::get('/employee-assignments/pending', [EmployeeInstitutionAssignmentController::class, 'pending']);
        Route::post('/employee/{employee}/assignments', [EmployeeInstitutionAssignmentController::class, 'store']);
        Route::put('/employee-assignments/{assignment}', [EmployeeInstitutionAssignmentController::class, 'update']);
        Route::post('/employee-assignments/{assignment}/approve', [EmployeeInstitutionAssignmentController::class, 'approve']);
        Route::post('/employee-assignments/{assignment}/reject', [EmployeeInstitutionAssignmentController::class, 'reject']);
        Route::post('/employee-assignments/{assignment}/end', [EmployeeInstitutionAssignmentController::class, 'end']);
    });

    // Class routes (tanpa cache list agar naik kelas/luluskan selalu dapat data segar)
    Route::middleware('module:class|student')->group(function () {
        Route::get('/class', [ClassController::class, 'index']);
        Route::get('/class/export/pdf', [ClassController::class, 'exportPdf'])->name('class.export.pdf');
        Route::post('/class/clone-to-year', [ClassController::class, 'cloneToYear'])->name('class.clone-to-year');
        Route::get('/class/{id}', [ClassController::class, 'show']);
        Route::get('/class/{id}/available-students', [ClassController::class, 'getAvailableStudents'])->name('class.available-students');
        Route::get('/class/{id}/students', [ClassController::class, 'getStudents'])->name('class.students');
    });
    Route::middleware('module:class')->group(function () {
        Route::post('/class', [ClassController::class, 'store']);
        Route::put('/class/{id}', [ClassController::class, 'update']);
        Route::delete('/class/{id}', [ClassController::class, 'destroy']);
        Route::post('/class/{id}/students', [ClassController::class, 'addStudents'])->name('class.add-students');
        Route::delete('/class/{id}/students/{studentId}', [ClassController::class, 'removeStudent'])->name('class.remove-student');
    });

    // Program keahlian (SMK/MAK) — master jurusan + dipakai Kaprog
    Route::middleware('module:class')->group(function () {
        Route::get('program-keahlian', [ProgramKeahlianController::class, 'index']);
        Route::post('program-keahlian', [ProgramKeahlianController::class, 'store']);
        Route::get('program-keahlian/{program_keahlian}', [ProgramKeahlianController::class, 'show']);
        Route::put('program-keahlian/{program_keahlian}', [ProgramKeahlianController::class, 'update']);
        Route::delete('program-keahlian/{program_keahlian}', [ProgramKeahlianController::class, 'destroy']);
    });

    // GET subjects: boleh diakses modul Jadwal atau Ujian Online (untuk dropdown mapel di bank soal, dll.)
    Route::middleware('module:schedule|online_exam')->group(function () {
        Route::get('subjects', [SubjectController::class, 'index'])->name('subjects.index.shared');
    });

    // Jadwal Pelajaran (Schedule) routes
    Route::middleware('module:schedule')->group(function () {
        Route::apiResource('subjects', SubjectController::class);
        Route::get('/lesson-schedule-templates', [LessonScheduleTemplateController::class, 'index'])->name('lesson-schedule-templates.index');
        Route::get('/lesson-schedule-templates/default', [LessonScheduleTemplateController::class, 'show'])->name('lesson-schedule-templates.show');
        Route::post('/lesson-schedule-templates', [LessonScheduleTemplateController::class, 'store'])->name('lesson-schedule-templates.store');
        Route::put('/lesson-schedule-templates/{id}', [LessonScheduleTemplateController::class, 'update'])->name('lesson-schedule-templates.update');
        Route::delete('/lesson-schedule-templates/{id}', [LessonScheduleTemplateController::class, 'destroy'])->name('lesson-schedule-templates.destroy');
        Route::put('/lesson-schedule-templates', [LessonScheduleTemplateController::class, 'upsert'])->name('lesson-schedule-templates.upsert');
        Route::put('/lesson-schedules/by-class/{classId}/template', [LessonScheduleTemplateController::class, 'assignToClass'])->name('lesson-schedules.by-class.template');
        Route::get('/lesson-schedules/by-class/{classId}/template/preview', [LessonScheduleTemplateController::class, 'previewAssignToClass'])->name('lesson-schedules.by-class.template.preview');
        Route::get('/lesson-schedules/export-pdf', [LessonScheduleController::class, 'exportPdf'])->name('lesson-schedules.export-pdf');
        Route::get('/lesson-schedules/my-teaching-load', [LessonScheduleController::class, 'myTeachingLoad'])->name('lesson-schedules.my-teaching-load');
        Route::get('/lesson-schedules/by-class/{classId}', [LessonScheduleController::class, 'byClass'])->name('lesson-schedules.by-class');
        Route::get('/lesson-schedules/by-teacher/{employeeId}', [LessonScheduleController::class, 'byTeacher'])->name('lesson-schedules.by-teacher');
        Route::get('/lesson-schedules/by-room/{roomId}', [LessonScheduleController::class, 'byRoom'])->name('lesson-schedules.by-room');
        Route::post('/lesson-schedules/copy-semester', [LessonScheduleController::class, 'copySemester'])->name('lesson-schedules.copy-semester');
        Route::delete('/lesson-schedules/by-class/{classId}', [LessonScheduleController::class, 'deleteByClass'])->name('lesson-schedules.delete-by-class');
        Route::delete('/lesson-schedules/by-semester/{semesterId}', [LessonScheduleController::class, 'deleteBySemester'])->name('lesson-schedules.delete-by-semester');
        Route::apiResource('lesson-schedules', LessonScheduleController::class);
    });

    // Teaching load for teacher/staff (tanpa wajib modul schedule — dipakai Buku Nilai & Jurnal)
    Route::get('/teacher/teaching-load', [LessonScheduleController::class, 'myTeachingLoad'])->name('teacher.teaching-load');

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
        // App branding (logo, favicon, hero halaman awal)
        Route::post('/app-branding/logo', [AppBrandingController::class, 'uploadLogo'])->name('app-branding.upload-logo');
        Route::post('/app-branding/favicon', [AppBrandingController::class, 'uploadFavicon'])->name('app-branding.upload-favicon');
        Route::put('/app-branding/hero', [AppBrandingController::class, 'updateHero'])->name('app-branding.update-hero');
        Route::post('/app-branding/hero-image', [AppBrandingController::class, 'uploadHeroImage'])->name('app-branding.upload-hero-image');
        Route::put('/app-branding/maintenance', [AppBrandingController::class, 'updateMaintenance'])->name('app-branding.update-maintenance');
    });

    // Semester routes
    Route::get('/semesters/active', [SemesterController::class, 'active'])->name('semesters.active');
    Route::get('/semesters/academic-year/{academicYearId}', [SemesterController::class, 'byAcademicYear'])->name('semesters.by-academic-year');
    Route::get('/semesters/academic-year/{academicYearId}/active', [SemesterController::class, 'activeForAcademicYear'])->name('semesters.active-for-academic-year');
    Route::post('/semesters/academic-year/{academicYearId}/auto-generate', [SemesterController::class, 'autoGenerate'])->name('semesters.auto-generate');
    Route::post('/semesters/{id}/activate', [SemesterController::class, 'activate'])->name('semesters.activate');
    Route::apiResource('semesters', SemesterController::class);

    // Academic Calendar routes (Kalender Akademik)
    Route::middleware('module:academic_calendar')->group(function () {
        Route::get('/academic-calendar/calendar', [AcademicCalendarController::class, 'calendar'])->name('academic-calendar.calendar');
        Route::get('/academic-calendar/upcoming', [AcademicCalendarController::class, 'upcoming'])->name('academic-calendar.upcoming');
        Route::apiResource('academic-calendar', AcademicCalendarController::class);
    });

    // Institution change request routes
    Route::get('/institution-change-requests/pending-count', [InstitutionChangeRequestController::class, 'pendingCount'])->name('institution-change-requests.pending-count');
    Route::post('/institution-change-requests/{id}/approve', [InstitutionChangeRequestController::class, 'approve'])->name('institution-change-requests.approve');
    Route::apiResource('institution-change-requests', InstitutionChangeRequestController::class)->except(['update', 'destroy']);

    // Feedback tickets (lapor bug / request fitur admin sekolah → super admin)
    Route::get('/feedback-tickets/open-count', [FeedbackTicketController::class, 'openCount'])->name('feedback-tickets.open-count');
    Route::post('/feedback-tickets/{id}/status', [FeedbackTicketController::class, 'update'])->name('feedback-tickets.status');
    Route::apiResource('feedback-tickets', FeedbackTicketController::class)->only(['index', 'store', 'show', 'update']);

    // Portal orang tua / wali murid
    Route::prefix('parent')->group(function () {
        Route::get('/dashboard', [ParentPortalController::class, 'dashboard'])->name('parent.dashboard');
        Route::get('/children', [ParentPortalController::class, 'children'])->name('parent.children');
        Route::get('/announcements', [ParentPortalController::class, 'announcements'])->name('parent.announcements');
        Route::get('/children/{studentId}/schedule', [ParentPortalController::class, 'schedule'])->name('parent.children.schedule');
        Route::get('/children/{studentId}/grades', [ParentPortalController::class, 'grades'])->name('parent.children.grades');
        Route::get('/children/{studentId}/attendance', [ParentPortalController::class, 'attendance'])->name('parent.children.attendance');
        Route::get('/children/{studentId}/violations', [ParentPortalController::class, 'violations'])->name('parent.children.violations');
    });

    // Konten publik sekolah (berita & galeri) — staff
    Route::middleware('module:school_content|institution')->group(function () {
        Route::get('/school-posts', [SchoolPostController::class, 'index']);
        Route::post('/school-posts', [SchoolPostController::class, 'store']);
        Route::get('/school-posts/{school_post}', [SchoolPostController::class, 'show']);
        Route::match(['put', 'post'], '/school-posts/{school_post}', [SchoolPostController::class, 'update']);
        Route::delete('/school-posts/{school_post}', [SchoolPostController::class, 'destroy']);
    });

    // Student change requests (siswa lengkapi data, admin setujui)
    Route::get('/student-change-requests/allowed-fields', [StudentChangeRequestController::class, 'allowedFields'])->name('student-change-requests.allowed-fields');
    Route::get('/student-change-requests/pending-count', [StudentChangeRequestController::class, 'pendingCount'])->name('student-change-requests.pending-count');
    Route::post('/student-change-requests/{id}/approve', [StudentChangeRequestController::class, 'approve'])->name('student-change-requests.approve');
    Route::apiResource('student-change-requests', StudentChangeRequestController::class)->only(['index', 'store', 'show']);

    // Teacher change requests (guru lengkapi/ubah data, admin setujui)
    Route::get('/teacher-change-requests/allowed-fields', [TeacherChangeRequestController::class, 'allowedFields'])->name('teacher-change-requests.allowed-fields');
    Route::get('/teacher-change-requests/pending-count', [TeacherChangeRequestController::class, 'pendingCount'])->name('teacher-change-requests.pending-count');
    Route::post('/teacher-change-requests/{id}/approve', [TeacherChangeRequestController::class, 'approve'])->name('teacher-change-requests.approve');
    Route::apiResource('teacher-change-requests', TeacherChangeRequestController::class)->only(['index', 'store', 'show']);
    Route::put('/teacher/profile', [TeacherChangeRequestController::class, 'updateMyProfile'])->name('teacher.profile.update');
    Route::post('/teacher/profile/photo', [TeacherChangeRequestController::class, 'uploadMyPhoto'])->name('teacher.profile.upload-photo');
    Route::delete('/teacher/profile/photo', [TeacherChangeRequestController::class, 'deleteMyPhoto'])->name('teacher.profile.delete-photo');

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
        Route::get('/rooms/{id}', [FacilityController::class, 'getRoom']);
        Route::post('/rooms', [FacilityController::class, 'createRoom']);
        Route::put('/rooms/{id}', [FacilityController::class, 'updateRoom']);
        Route::delete('/rooms/{id}', [FacilityController::class, 'deleteRoom']);
        Route::get('/export/pdf', [FacilityController::class, 'exportPdf']);
        Route::get('/lab-report', [FacilityController::class, 'getLabReport']);
        Route::get('/lab-report/export', [FacilityController::class, 'exportLabReport']);
        Route::get('/labs/{id}/export', [FacilityController::class, 'exportLabReport']);
        Route::get('/my-labs', [FacilityController::class, 'getMyLabs']);

        // Lab booking
        Route::get('/lab-bookings', [LabBookingController::class, 'index']);
        Route::post('/lab-bookings', [LabBookingController::class, 'store']);
        Route::post('/lab-bookings/{id}/approve', [LabBookingController::class, 'approve']);
        Route::post('/lab-bookings/{id}/reject', [LabBookingController::class, 'reject']);
        Route::post('/lab-bookings/{id}/cancel', [LabBookingController::class, 'cancel']);

        // Lab usage journal
        Route::get('/lab-journals', [LabUsageJournalController::class, 'index']);
        Route::post('/lab-journals', [LabUsageJournalController::class, 'store']);
        Route::put('/lab-journals/{id}', [LabUsageJournalController::class, 'update']);
        Route::delete('/lab-journals/{id}', [LabUsageJournalController::class, 'destroy']);
    });

    // Lab booking untuk guru/staff (tanpa wajib modul facility)
    Route::prefix('lab-booking')->group(function () {
        Route::get('/labs', [LabBookingController::class, 'labsForBooking']);
        Route::get('/', [LabBookingController::class, 'index']);
        Route::post('/', [LabBookingController::class, 'store']);
        Route::post('/{id}/cancel', [LabBookingController::class, 'cancel']);
    });

    // Inventory routes (Inventaris)
    Route::prefix('inventory')->middleware('module:inventory')->group(function () {
        // Category routes
        Route::apiResource('categories', InventoryCategoryController::class)->names('inventory.categories');

        // Item routes
        Route::get('/items/import/template', [InventoryController::class, 'importTemplate'])->name('inventory.items.import.template');
        Route::post('/items/import', [InventoryController::class, 'import'])->name('inventory.items.import');
        Route::get('/items/export/excel', [InventoryController::class, 'exportExcel'])->name('inventory.items.export.excel');
        Route::apiResource('items', InventoryController::class);
        Route::post('/items/{item}/dispose', [InventoryController::class, 'dispose'])->name('inventory.items.dispose');
        Route::post('/items/{item}/split-assets', [InventoryController::class, 'splitAssets'])->name('inventory.items.split-assets');
        Route::get('/items/{item}/export/kib', [InventoryReportController::class, 'exportKib'])->name('inventory.items.export.kib');
        Route::get('/assets/{asset}/export/kib', [InventoryReportController::class, 'exportAssetKib'])->name('inventory.assets.export.kib');
        Route::post('/items/{id}/restore', [InventoryController::class, 'restore']);

        Route::post('/assets/qr/bulk', [InventoryAssetController::class, 'qrBulk'])->name('inventory.assets.qr.bulk');
        Route::post('/assets/qr/print-pdf', [InventoryAssetController::class, 'printQrPdf'])->name('inventory.assets.qr.print-pdf');
        Route::get('/assets/resolve-qr', [InventoryAssetController::class, 'resolveByQr'])->name('inventory.assets.resolve-qr');
        Route::get('/assets/{asset}/qr', [InventoryAssetController::class, 'qrImage'])->name('inventory.assets.qr');
        Route::post('/assets/{asset}/dispose', [InventoryAssetController::class, 'dispose'])->name('inventory.assets.dispose');
        Route::post('/assets/{asset}/transfer', [InventoryAssetController::class, 'transfer'])->name('inventory.assets.transfer');
        Route::apiResource('assets', InventoryAssetController::class)->names('inventory.assets');

        Route::get('/asset-movements', [InventoryAssetMovementController::class, 'index'])->name('inventory.asset-movements.index');

        Route::get('/stock-opnames', [InventoryStockOpnameController::class, 'index'])->name('inventory.stock-opnames.index');
        Route::post('/stock-opnames', [InventoryStockOpnameController::class, 'store'])->name('inventory.stock-opnames.store');
        Route::get('/stock-opnames/{opname}', [InventoryStockOpnameController::class, 'show'])->name('inventory.stock-opnames.show');
        Route::post('/stock-opnames/{opname}/refresh-lines', [InventoryStockOpnameController::class, 'refreshLines'])->name('inventory.stock-opnames.refresh-lines');
        Route::put('/stock-opnames/{opname}/lines/{line}', [InventoryStockOpnameController::class, 'updateLine'])->name('inventory.stock-opnames.update-line');
        Route::put('/stock-opnames/{opname}/asset-lines/{line}', [InventoryStockOpnameController::class, 'updateAssetLine'])->name('inventory.stock-opnames.update-asset-line');
        Route::post('/stock-opnames/{opname}/finalize', [InventoryStockOpnameController::class, 'finalize'])->name('inventory.stock-opnames.finalize');
        Route::post('/stock-opnames/{opname}/cancel', [InventoryStockOpnameController::class, 'cancel'])->name('inventory.stock-opnames.cancel');

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
        Route::put('/loans/{loan}', [InventoryLoanController::class, 'update'])->name('inventory.loans.update');
        Route::post('/loans/{loan}/return', [InventoryLoanController::class, 'return'])->name('inventory.loans.return');

        Route::get('/disposals', [InventoryDisposalController::class, 'index'])->name('inventory.disposals.index');
        Route::put('/disposals/{disposal}', [InventoryDisposalController::class, 'update'])->name('inventory.disposals.update');
        Route::post('/disposals/{disposal}/document', [InventoryDisposalController::class, 'uploadDocument'])->name('inventory.disposals.upload-document');
        Route::delete('/disposals/{disposal}/document', [InventoryDisposalController::class, 'deleteDocument'])->name('inventory.disposals.delete-document');
        Route::delete('/disposals/{disposal}', [InventoryDisposalController::class, 'destroy'])->name('inventory.disposals.destroy');

        // Report routes
        Route::prefix('reports')->group(function () {
            Route::get('/statistics', [InventoryReportController::class, 'statistics'])->name('inventory.reports.statistics');
            Route::get('/stock', [InventoryReportController::class, 'stock'])->name('inventory.reports.stock');
            Route::get('/by-category', [InventoryReportController::class, 'byCategory'])->name('inventory.reports.by-category');
            Route::get('/by-location', [InventoryReportController::class, 'byLocation'])->name('inventory.reports.by-location');
            Route::get('/damaged-missing', [InventoryReportController::class, 'damagedMissing'])->name('inventory.reports.damaged-missing');
            Route::get('/loaned', [InventoryReportController::class, 'loaned'])->name('inventory.reports.loaned');
            Route::get('/asset-value', [InventoryReportController::class, 'assetValue'])->name('inventory.reports.asset-value');
            Route::get('/maintenance', [InventoryReportController::class, 'maintenance'])->name('inventory.reports.maintenance');
            Route::get('/transactions', [InventoryReportController::class, 'transactions'])->name('inventory.reports.transactions');
            Route::get('/asset-movements', [InventoryReportController::class, 'assetMovements'])->name('inventory.reports.asset-movements');
            Route::get('/disposed', [InventoryReportController::class, 'disposed'])->name('inventory.reports.disposed');
            Route::get('/export/pdf', [InventoryReportController::class, 'exportPdf'])->name('inventory.reports.export.pdf');
            Route::get('/export/excel', [InventoryReportController::class, 'exportExcel'])->name('inventory.reports.export.excel');
        });
    });

    // Report routes (Laporan/Statistik)
    Route::prefix('report')->middleware('module:report')->group(function () {
        Route::get('/institution/{institutionId?}', [ReportController::class, 'getStatistics'])->name('report.statistics');
    });

    // Teacher dashboard
    Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');
    Route::get('/teacher/today-sessions', [TeacherDashboardController::class, 'todaySessions'])->name('teacher.today-sessions');
    Route::get('/teacher/today-sessions/export-pdf', [TeacherDashboardController::class, 'exportTodaySessionsPdf'])->name('teacher.today-sessions.export-pdf');
    Route::get('/teacher/dashboard/classes/{id}/students', [TeacherDashboardController::class, 'classStudents'])
        ->name('teacher.dashboard.class-students');

    // Wali kelas hub (scoped by homeroom ownership, no extra module grant)
    Route::prefix('teacher/wali')->group(function () {
        Route::get('/classes/{classId}/students/{studentId}', [WaliKelasController::class, 'showStudent']);
        Route::patch('/classes/{classId}/students/{studentId}/login-fields', [WaliKelasController::class, 'updateLoginFields']);
        Route::patch('/classes/{classId}/students/{studentId}', [WaliKelasController::class, 'updateStudent']);
        Route::post('/classes/{classId}/students/{studentId}/photo', [WaliKelasController::class, 'uploadPhoto']);
        Route::delete('/classes/{classId}/students/{studentId}/photo', [WaliKelasController::class, 'deletePhoto']);
        Route::post('/classes/{classId}/students/{studentId}/ensure-account', [WaliKelasController::class, 'ensureStudentAccount']);
        Route::post('/classes/{classId}/students/{studentId}/reset-password', [WaliKelasController::class, 'resetStudentPassword']);
        Route::post('/classes/{classId}/ensure-accounts', [WaliKelasController::class, 'ensureAccountsBulk']);
        Route::get('/classes/{classId}/dashboard', [WaliKelasController::class, 'dashboard']);
        Route::get('/classes/{classId}/attendance-summary', [WaliKelasController::class, 'attendanceSummary']);
        Route::get('/classes/{classId}/grades-overview', [WaliKelasController::class, 'gradesOverview']);
        Route::get('/classes/{classId}/students/{studentId}/notes', [WaliKelasController::class, 'indexNotes']);
        Route::post('/classes/{classId}/students/{studentId}/notes', [WaliKelasController::class, 'storeNote']);
        Route::put('/classes/{classId}/students/{studentId}/notes/{noteId}', [WaliKelasController::class, 'updateNote']);
        Route::delete('/classes/{classId}/students/{studentId}/notes/{noteId}', [WaliKelasController::class, 'destroyNote']);
        Route::get('/classes/{classId}/schedule', [WaliKelasController::class, 'schedule']);
        Route::get('/classes/{classId}/schedule/export-pdf', [WaliKelasController::class, 'exportSchedulePdf']);
        Route::get('/violations', [WaliKelasController::class, 'indexViolations']);
        Route::post('/violations', [WaliKelasController::class, 'storeViolation']);
        Route::get('/violation-types', [WaliKelasController::class, 'violationTypes']);
        Route::get('/achievements', [WaliKelasController::class, 'indexAchievements']);
        Route::post('/achievements', [WaliKelasController::class, 'storeAchievement']);
        Route::get('/achievement-types', [WaliKelasController::class, 'achievementTypes']);
        Route::get('/mutations', [WaliKelasController::class, 'indexMutations']);
        Route::post('/mutations', [WaliKelasController::class, 'storeMutation']);
        Route::get('/classes/{classId}/export/roster', [WaliKelasController::class, 'exportRoster']);
        Route::get('/classes/{classId}/export/identitas', [WaliKelasController::class, 'exportIdentitas']);
        Route::get('/classes/{classId}/export/contacts', [WaliKelasController::class, 'exportContacts']);
        Route::get('/classes/{classId}/export/attendance', [WaliKelasController::class, 'exportAttendance']);
        Route::get('/classes/{classId}/finance/summary', [WaliKelasController::class, 'financeSummary'])->name('wali.finance.summary');
        Route::get('/classes/{classId}/finance/fee-types', [WaliKelasController::class, 'financeFeeTypes'])->name('wali.finance.fee-types');
        Route::get('/classes/{classId}/finance/invoices', [WaliKelasController::class, 'financeInvoices'])->name('wali.finance.invoices');
        Route::post('/classes/{classId}/finance/invoices/generate', [WaliKelasController::class, 'generateFinanceInvoices'])->name('wali.finance.invoices.generate');
        Route::post('/classes/{classId}/finance/payments', [WaliKelasController::class, 'storeFinancePayment'])->name('wali.finance.payments.store');
        Route::get('/classes/{classId}/finance/payments/{paymentId}/receipt', [WaliKelasController::class, 'financePaymentReceipt'])->name('wali.finance.payments.receipt');
    });

    // Super admin dashboard & institution status
    Route::get('/super-admin/dashboard', [SuperAdminDashboardController::class, 'index'])->name('super-admin.dashboard');
    Route::patch('/institution/{id}/status', [InstitutionController::class, 'updateStatus'])->name('institution.update-status');
    Route::post('/institution/{id}/status', [InstitutionController::class, 'updateStatus'])->name('institution.update-status-post');

    // Super admin: institution admins & onboarding
    Route::get('/super-admin/institution-admins', [InstitutionAdminController::class, 'index'])->name('super-admin.institution-admins.index');
    Route::post('/super-admin/institution-admins', [InstitutionAdminController::class, 'store'])->name('super-admin.institution-admins.store');
    Route::post('/super-admin/institution-admins/{id}/reset-password', [InstitutionAdminController::class, 'resetPassword'])->name('super-admin.institution-admins.reset-password');
    Route::get('/password-reset-requests/pending-count', [PasswordResetRequestController::class, 'pendingCount'])->name('password-reset-requests.pending-count');
    Route::get('/password-reset-requests', [PasswordResetRequestController::class, 'index'])->name('password-reset-requests.index');
    Route::post('/password-reset-requests/{id}/process', [PasswordResetRequestController::class, 'process'])->name('password-reset-requests.process');
    Route::post('/password-reset-requests/{id}/reject', [PasswordResetRequestController::class, 'reject'])->name('password-reset-requests.reject');
    Route::post('/super-admin/institution-admins/{id}/status', [InstitutionAdminController::class, 'updateStatus'])->name('super-admin.institution-admins.status');
    Route::post('/super-admin/onboard', [InstitutionAdminController::class, 'onboard'])->name('super-admin.onboard');

    // Super admin: adoption, broadcast, aggregate reports
    Route::get('/super-admin/adoption', [SuperAdminAdoptionController::class, 'index'])->name('super-admin.adoption');
    Route::get('/super-admin/broadcasts', [SuperAdminBroadcastController::class, 'index'])->name('super-admin.broadcasts.index');
    Route::post('/super-admin/broadcasts', [SuperAdminBroadcastController::class, 'store'])->name('super-admin.broadcasts.store');
    Route::get('/super-admin/broadcasts/{id}', [SuperAdminBroadcastController::class, 'show'])->name('super-admin.broadcasts.show');
    Route::get('/super-admin/releases', [SuperAdminReleaseController::class, 'index'])->name('super-admin.releases.index');
    Route::post('/super-admin/releases', [SuperAdminReleaseController::class, 'store'])->name('super-admin.releases.store');
    Route::get('/super-admin/releases/{id}', [SuperAdminReleaseController::class, 'show'])->name('super-admin.releases.show');
    Route::put('/super-admin/releases/{id}', [SuperAdminReleaseController::class, 'update'])->name('super-admin.releases.update');
    Route::delete('/super-admin/releases/{id}', [SuperAdminReleaseController::class, 'destroy'])->name('super-admin.releases.destroy');
    Route::get('/super-admin/reports/aggregate', [SuperAdminReportController::class, 'index'])->name('super-admin.reports.aggregate');
    Route::get('/super-admin/reports/aggregate/export', [SuperAdminReportController::class, 'export'])->name('super-admin.reports.aggregate.export');
    Route::post('/super-admin/institution-admins/{id}/impersonate', [SuperAdminImpersonationController::class, 'start'])->name('super-admin.impersonate.start');
    Route::post('/super-admin/impersonate/stop', [SuperAdminImpersonationController::class, 'stop'])->name('super-admin.impersonate.stop');

    // Super admin: database backup (mysqldump → .sql.gz)
    Route::middleware(\App\Http\Middleware\EnsureSuperAdmin::class)->prefix('super-admin/database-backups')->group(function () {
        Route::get('/', [SuperAdminDatabaseBackupController::class, 'index'])->name('super-admin.database-backups.index');
        Route::post('/', [SuperAdminDatabaseBackupController::class, 'store'])->name('super-admin.database-backups.store');
        Route::get('/{filename}/download', [SuperAdminDatabaseBackupController::class, 'download'])
            ->where('filename', 'iss-db-\d{8}-\d{6}\.sql(?:\.gz)?')
            ->name('super-admin.database-backups.download');
        Route::delete('/{filename}', [SuperAdminDatabaseBackupController::class, 'destroy'])
            ->where('filename', 'iss-db-\d{8}-\d{6}\.sql(?:\.gz)?')
            ->name('super-admin.database-backups.destroy');
    });

    // Super admin: monetisasi (dark launch — default tersembunyi dari sekolah)
    Route::middleware(\App\Http\Middleware\EnsureSuperAdmin::class)->prefix('super-admin/monetization')->group(function () {
        Route::get('/summary', [SuperAdminMonetizationController::class, 'summary'])->name('super-admin.monetization.summary');
        Route::put('/launch', [SuperAdminMonetizationController::class, 'updateLaunch'])->name('super-admin.monetization.launch');
        Route::get('/plans', [SuperAdminMonetizationController::class, 'plans'])->name('super-admin.monetization.plans');
        Route::post('/plans', [SuperAdminMonetizationController::class, 'storePlan'])->name('super-admin.monetization.plans.store');
        Route::put('/plans/{id}', [SuperAdminMonetizationController::class, 'updatePlan'])->name('super-admin.monetization.plans.update');
        Route::get('/addons', [SuperAdminMonetizationController::class, 'addons'])->name('super-admin.monetization.addons');
        Route::put('/addons/{id}', [SuperAdminMonetizationController::class, 'updateAddon'])->name('super-admin.monetization.addons.update');
        Route::get('/institutions', [SuperAdminMonetizationController::class, 'institutions'])->name('super-admin.monetization.institutions');
        Route::put('/institutions/{institutionId}/subscription', [SuperAdminMonetizationController::class, 'upsertInstitutionSubscription'])
            ->name('super-admin.monetization.institutions.subscription');
        Route::put('/institutions/{institutionId}/addons', [SuperAdminMonetizationController::class, 'upsertInstitutionAddon'])
            ->name('super-admin.monetization.institutions.addons');
    });

    // Sisi sekolah: hanya hidup setelah Super Admin klik "Tampilkan ke sekolah"
    Route::middleware('monetization.launched')->prefix('billing')->group(function () {
        Route::get('/overview', [InstitutionMonetizationController::class, 'overview'])->name('billing.overview');
    });

    // Inbox disposisi: penerima (guru) tanpa modul Persuratan penuh
    Route::get('/dispositions/pending', [DispositionController::class, 'pending'])->name('dispositions.pending');
    Route::post('/correspondence/dispositions/{id}/complete', [DispositionController::class, 'complete'])
        ->name('correspondence.dispositions.complete');

    // Correspondence routes (Persuratan) — admin / tugas administratif
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

        // Disposition manage (buat/edit/hapus) — perlu modul penuh
        Route::get('/{correspondenceId}/dispositions', [DispositionController::class, 'index'])->name('correspondence.dispositions.index');
        Route::post('/{correspondenceId}/dispositions', [DispositionController::class, 'store'])->name('correspondence.dispositions.store');
        Route::put('/dispositions/{id}', [DispositionController::class, 'update'])->name('correspondence.dispositions.update');
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

    // Editor Persuratan (template + surat Word-like)
    Route::middleware('module:correspondence')->group(function () {
        Route::get('templates/placeholders', [TemplateSuratController::class, 'placeholders'])->name('templates.placeholders');
        Route::post('templates/{id}/toggle-status', [TemplateSuratController::class, 'toggleStatus'])->name('templates.toggle-status');
        Route::post('templates/{id}/fork', [TemplateSuratController::class, 'fork'])->name('templates.fork');
        Route::apiResource('templates', TemplateSuratController::class)->parameters(['templates' => 'id']);

        Route::post('surat/generate', [SuratController::class, 'generate'])->name('surat.generate');
        Route::post('surat/upload-image', [SuratController::class, 'uploadImage'])->name('surat.upload-image');
        Route::get('surat/classes', [SuratController::class, 'classes'])->name('surat.classes');
        Route::get('surat/letterhead-context', [SuratController::class, 'letterheadContext'])->name('surat.letterhead-context');
        Route::get('surat/students', [SuratController::class, 'students'])->name('surat.students');
        Route::get('surat/employees', [SuratController::class, 'employees'])->name('surat.employees');
        Route::get('surat/{id}/pdf', [SuratController::class, 'exportPdf'])->name('surat.pdf');
        Route::get('surat/{id}/print', [SuratController::class, 'print'])->name('surat.print');
        Route::get('surat/{id}/layout', [SuratController::class, 'layout'])->name('surat.layout');
        Route::post('surat/{id}/terbitkan', [SuratController::class, 'publish'])->name('surat.publish');
        Route::apiResource('surat', SuratController::class)->parameters(['surat' => 'id']);

        Route::apiResource('kop-surat', KopSuratController::class)->parameters(['kop-surat' => 'id']);
        Route::apiResource('aset-tanda-tangan', AsetTandaTanganController::class)->parameters(['aset-tanda-tangan' => 'id']);
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

    // Perpustakaan — ebook baca (siswa & staf institusi, tanpa module library)
    Route::prefix('library')->group(function () {
        Route::get('ebooks', [LibraryBookController::class, 'ebooks'])->name('library.ebooks.index');
        Route::get('ebooks/categories', [LibraryBookCategoryController::class, 'index'])->name('library.ebooks.categories');
        Route::get('books/{book}/ebook', [LibraryBookController::class, 'streamEbook'])->name('library.books.ebook');
    });

    // Perpustakaan (Library) — kelola staf
    Route::prefix('library')->middleware('module:library')->group(function () {
        Route::apiResource('categories', LibraryBookCategoryController::class)->names('library.categories');
        Route::get('books/import/template', [LibraryBookController::class, 'importTemplate'])->name('library.books.import.template');
        Route::post('books/import', [LibraryBookController::class, 'import'])->name('library.books.import');
        Route::get('books/export/csv', [LibraryBookController::class, 'exportCsv'])->name('library.books.export.csv');
        Route::get('books/export/pdf', [LibraryBookController::class, 'exportPdf'])->name('library.books.export.pdf');
        Route::apiResource('books', LibraryBookController::class);
        Route::post('books/{book}', [LibraryBookController::class, 'update'])->name('library.books.update.post'); // FormData + _method
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
            Route::get('top-ebooks', [LibraryReportController::class, 'topEbooks'])->name('library.reports.top-ebooks');
            Route::get('loans-by-month', [LibraryReportController::class, 'loansByMonth'])->name('library.reports.loans-by-month');
            Route::get('ebook-views-by-month', [LibraryReportController::class, 'ebookViewsByMonth'])->name('library.reports.ebook-views-by-month');
            Route::get('export/loans-pdf', [LibraryReportController::class, 'exportLoansPdf'])->name('library.reports.export.loans-pdf');
            Route::get('export/fines-pdf', [LibraryReportController::class, 'exportFinesPdf'])->name('library.reports.export.fines-pdf');
        });
    });

    // Portal siswa: daftar ekskul & rekap nilai (auth saja; controller membatasi data sendiri)
    // Harus sebelum route {extracurricular} agar "by-student" tidak tertangkap sebagai ID.
    Route::get('extracurriculars/by-student/{studentId}', [ExtracurricularController::class, 'getByStudent'])->name('extracurriculars.by-student');
    Route::get('extracurriculars/{extracurricular}/my-grades', [ExtracurricularActivityController::class, 'myGrades'])->name('extracurriculars.my-grades');

    // Ekstrakurikuler
    Route::middleware('module:extracurricular')->group(function () {
        Route::get('extracurriculars/classes-lite', [ExtracurricularController::class, 'classesLite'])->name('extracurriculars.classes-lite');
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

        Route::get('extracurriculars/{extracurricular}/sessions', [ExtracurricularActivityController::class, 'listSessions'])->name('extracurriculars.sessions.index');
        Route::post('extracurriculars/{extracurricular}/sessions', [ExtracurricularActivityController::class, 'storeSession'])->name('extracurriculars.sessions.store');
        Route::put('extracurriculars/{extracurricular}/sessions/{extracurricularSession}', [ExtracurricularActivityController::class, 'updateSession'])->name('extracurriculars.sessions.update');
        Route::delete('extracurriculars/{extracurricular}/sessions/{extracurricularSession}', [ExtracurricularActivityController::class, 'destroySession'])->name('extracurriculars.sessions.destroy');
        Route::get('extracurriculars/{extracurricular}/sessions/{extracurricularSession}/attendances', [ExtracurricularActivityController::class, 'getAttendances'])->name('extracurriculars.attendances.index');
        Route::put('extracurriculars/{extracurricular}/sessions/{extracurricularSession}/attendances', [ExtracurricularActivityController::class, 'saveAttendances'])->name('extracurriculars.attendances.save');
        Route::get('extracurriculars/{extracurricular}/sessions/{extracurricularSession}/grades', [ExtracurricularActivityController::class, 'getSessionGrades'])->name('extracurriculars.session-grades.index');
        Route::put('extracurriculars/{extracurricular}/sessions/{extracurricularSession}/grades', [ExtracurricularActivityController::class, 'saveSessionGrades'])->name('extracurriculars.session-grades.save');
        Route::get('extracurriculars/{extracurricular}/grades', [ExtracurricularActivityController::class, 'listGrades'])->name('extracurriculars.grades.index');
        Route::put('extracurriculars/{extracurricular}/grades', [ExtracurricularActivityController::class, 'saveGrades'])->name('extracurriculars.grades.save');
        Route::get('extracurriculars/{extracurricular}/report', [ExtracurricularActivityController::class, 'report'])->name('extracurriculars.report');
        Route::get('extracurriculars/{extracurricular}/report/export', [ExtracurricularActivityController::class, 'exportReportCsv'])->name('extracurriculars.report.export');
        Route::get('extracurriculars/{extracurricular}/report/pdf', [ExtracurricularActivityController::class, 'exportReportPdf'])->name('extracurriculars.report.pdf');
    });

    // PPDB (Penerimaan Peserta Didik Baru)
    Route::middleware('module:ppdb')->group(function () {
        Route::get('ppdb/summary', [PpdbDashboardController::class, 'summary'])->name('ppdb.summary');
        Route::get('ppdb-periods/{ppdb_period}/statistics', [PpdbPeriodController::class, 'statistics'])->name('ppdb-periods.statistics');
        Route::apiResource('ppdb-periods', PpdbPeriodController::class);
        Route::apiResource('ppdb-channels', PpdbChannelController::class);
        Route::get('ppdb-applicants', [PpdbApplicantController::class, 'index'])->name('ppdb-applicants.index');
        Route::get('ppdb-applicants/export', [PpdbApplicantController::class, 'export'])->name('ppdb-applicants.export');
        Route::post('ppdb-applicants/bulk-verification', [PpdbApplicantController::class, 'bulkVerification'])->name('ppdb-applicants.bulk-verification');
        Route::post('ppdb-applicants/bulk-result', [PpdbApplicantController::class, 'bulkResult'])->name('ppdb-applicants.bulk-result');
        Route::post('ppdb-applicants', [PpdbApplicantController::class, 'store'])->name('ppdb-applicants.store');
        Route::get('ppdb-applicants/{ppdb_applicant}', [PpdbApplicantController::class, 'show'])->name('ppdb-applicants.show');
        Route::put('ppdb-applicants/{ppdb_applicant}', [PpdbApplicantController::class, 'update'])->name('ppdb-applicants.update');
        Route::delete('ppdb-applicants/{ppdb_applicant}', [PpdbApplicantController::class, 'destroy'])->name('ppdb-applicants.destroy');
        Route::post('ppdb-applicants/{ppdb_applicant}/verification', [PpdbApplicantController::class, 'setVerification'])->name('ppdb-applicants.verification');
        Route::post('ppdb-applicants/{ppdb_applicant}/submit', [PpdbApplicantController::class, 'submit'])->name('ppdb-applicants.submit');
        Route::post('ppdb-applicants/{ppdb_applicant}/result', [PpdbApplicantController::class, 'setResult'])->name('ppdb-applicants.result');
        Route::post('ppdb-applicants/{ppdb_applicant}/payment', [PpdbApplicantController::class, 'setPayment'])->name('ppdb-applicants.payment');
        Route::post('ppdb-applicants/{ppdb_applicant}/confirm-re-registration', [PpdbApplicantController::class, 'confirmReRegistration'])->name('ppdb-applicants.confirm-re-registration');
        Route::post('ppdb-applicants/{ppdb_applicant}/convert-to-student', [PpdbApplicantController::class, 'convertToStudent'])->name('ppdb-applicants.convert-to-student');
        Route::get('ppdb-applicants/{ppdb_applicant}/registration-slip', [PpdbApplicantController::class, 'registrationSlip'])->name('ppdb-applicants.registration-slip');
        Route::post('ppdb-applicants/{ppdb_applicant}/documents', [PpdbApplicantController::class, 'uploadDocument'])->name('ppdb-applicants.upload-document');
        Route::delete('ppdb-applicants/{ppdb_applicant}/documents/{documentId}', [PpdbApplicantController::class, 'deleteDocument'])->name('ppdb-applicants.delete-document');
        Route::get('ppdb-applicants/{ppdb_applicant}/documents/{documentId}/download', [PpdbApplicantController::class, 'downloadDocument'])->name('ppdb-applicants.download-document');
    });

    // Keuangan sekolah (SPP, tagihan, pembayaran, tunggakan, laporan)
    Route::middleware('module:finance')->prefix('finance')->group(function () {
        Route::get('summary', [FinanceDashboardController::class, 'summary'])->name('finance.summary');
        Route::get('classes-lite', [FinancePickerController::class, 'classesLite'])->name('finance.classes-lite');
        Route::get('students-lite', [FinancePickerController::class, 'studentsLite'])->name('finance.students-lite');
        Route::apiResource('fee-types', FinanceFeeTypeController::class)->parameters(['fee-types' => 'fee_type']);
        Route::get('invoices/export', [FinanceInvoiceController::class, 'export'])->name('finance.invoices.export');
        Route::get('invoices', [FinanceInvoiceController::class, 'index'])->name('finance.invoices.index');
        Route::post('invoices/generate', [FinanceInvoiceController::class, 'generate'])->name('finance.invoices.generate');
        Route::get('invoices/{invoice}', [FinanceInvoiceController::class, 'show'])->name('finance.invoices.show');
        Route::put('invoices/{invoice}', [FinanceInvoiceController::class, 'update'])->name('finance.invoices.update');
        Route::delete('invoices/{invoice}', [FinanceInvoiceController::class, 'destroy'])->name('finance.invoices.destroy');
        Route::get('payments/export', [FinancePaymentController::class, 'export'])->name('finance.payments.export');
        Route::get('payments', [FinancePaymentController::class, 'index'])->name('finance.payments.index');
        Route::post('payments', [FinancePaymentController::class, 'store'])->name('finance.payments.store');
        Route::get('payments/{payment}/receipt', [FinancePaymentController::class, 'receipt'])->name('finance.payments.receipt');
        Route::get('payments/{payment}', [FinancePaymentController::class, 'show'])->name('finance.payments.show');
        Route::delete('payments/{payment}', [FinancePaymentController::class, 'destroy'])->name('finance.payments.destroy');
        Route::get('expenses/export', [FinanceExpenseController::class, 'export'])->name('finance.expenses.export');
        Route::get('expenses', [FinanceExpenseController::class, 'index'])->name('finance.expenses.index');
        Route::post('expenses', [FinanceExpenseController::class, 'store'])->name('finance.expenses.store');
        Route::get('expenses/{expense}', [FinanceExpenseController::class, 'show'])->name('finance.expenses.show');
        Route::delete('expenses/{expense}', [FinanceExpenseController::class, 'destroy'])->name('finance.expenses.destroy');
    });

    // Penggajian pegawai
    Route::middleware('module:payroll')->prefix('payroll')->group(function () {
        Route::get('components', [PayrollComponentController::class, 'index'])->name('payroll.components.index');
        Route::post('components', [PayrollComponentController::class, 'store'])->name('payroll.components.store');
        Route::put('components/{component}', [PayrollComponentController::class, 'update'])->name('payroll.components.update');
        Route::delete('components/{component}', [PayrollComponentController::class, 'destroy'])->name('payroll.components.destroy');

        Route::get('position-allowances', [PayrollPositionAllowanceController::class, 'index'])->name('payroll.position-allowances.index');
        Route::put('position-allowances', [PayrollPositionAllowanceController::class, 'sync'])->name('payroll.position-allowances.sync');

        Route::get('employee-profiles/employees-lite', [PayrollEmployeeProfileController::class, 'employeesLite'])->name('payroll.employee-profiles.employees-lite');
        Route::get('employee-profiles', [PayrollEmployeeProfileController::class, 'index'])->name('payroll.employee-profiles.index');
        Route::post('employee-profiles', [PayrollEmployeeProfileController::class, 'store'])->name('payroll.employee-profiles.store');
        Route::put('employee-profiles/{profile}', [PayrollEmployeeProfileController::class, 'update'])->name('payroll.employee-profiles.update');

        Route::get('periods', [PayrollPeriodController::class, 'index'])->name('payroll.periods.index');
        Route::post('periods', [PayrollPeriodController::class, 'store'])->name('payroll.periods.store');
        Route::post('periods/{period}/close', [PayrollPeriodController::class, 'close'])->name('payroll.periods.close');

        Route::get('runs', [PayrollRunController::class, 'index'])->name('payroll.runs.index');
        Route::post('runs/generate', [PayrollRunController::class, 'generate'])->name('payroll.runs.generate');
        Route::get('runs/{run}', [PayrollRunController::class, 'show'])->name('payroll.runs.show');
        Route::get('runs/{run}/export/excel', [PayrollRunController::class, 'exportExcel'])->name('payroll.runs.export.excel');
        Route::get('runs/{run}/export/pdf', [PayrollRunController::class, 'exportPdf'])->name('payroll.runs.export.pdf');
        Route::get('runs/{run}/slips', [PayrollRunController::class, 'slips'])->name('payroll.runs.slips');
        Route::post('runs/{run}/finalize', [PayrollRunController::class, 'finalize'])->name('payroll.runs.finalize');
        Route::post('runs/{run}/mark-paid', [PayrollRunController::class, 'markPaid'])->name('payroll.runs.mark-paid');
        Route::post('runs/{run}/unpay', [PayrollRunController::class, 'unpay'])->name('payroll.runs.unpay');
        Route::post('runs/{run}/reopen', [PayrollRunController::class, 'reopen'])->name('payroll.runs.reopen');
        Route::delete('runs/{run}', [PayrollRunController::class, 'destroy'])->name('payroll.runs.destroy');

        Route::get('slips/{slip}', [PayrollSlipController::class, 'show'])->name('payroll.slips.show');
        Route::put('slips/{slip}', [PayrollSlipController::class, 'update'])->name('payroll.slips.update');
        Route::get('slips/{slip}/pdf', [PayrollSlipController::class, 'pdf'])->name('payroll.slips.pdf');

        Route::get('expenses', [FinanceExpenseController::class, 'index'])->name('payroll.expenses.index');
        Route::get('expenses/export', [FinanceExpenseController::class, 'export'])->name('payroll.expenses.export');
        Route::get('expenses/{expense}', [FinanceExpenseController::class, 'show'])->name('payroll.expenses.show');
    });

    // Ujian Online (admin/guru: exam, session, bank soal, peserta, kendali)
    Route::middleware(['module:online_exam', 'online_exam.entitled'])->prefix('exam')->group(function () {
        Route::get('exams/by-code/{code}', [ExamController::class, 'showByCode'])->name('exams.by-code');
        Route::apiResource('exams', ExamController::class);
        Route::post('exams/{exam}/questions', [ExamController::class, 'attachQuestions'])->name('exams.attach-questions');
        Route::get('sessions', [ExamSessionController::class, 'index'])->name('exam.sessions.index');
        Route::post('sessions', [ExamSessionController::class, 'store'])->name('exam.sessions.store');
        Route::get('sessions/{exam_session}', [ExamSessionController::class, 'show'])->name('exam.sessions.show');
        Route::put('sessions/{exam_session}', [ExamSessionController::class, 'update'])->name('exam.sessions.update');
        Route::delete('sessions/{exam_session}', [ExamSessionController::class, 'destroy'])->name('exam.sessions.destroy');
        Route::post('sessions/{exam_session}/start', [ExamControlController::class, 'startSession'])->name('exam.sessions.start');
        Route::post('sessions/{exam_session}/regenerate-entry-pin', [ExamControlController::class, 'regenerateEntryPin'])->name('exam.sessions.regenerate-entry-pin');
        Route::post('sessions/{exam_session}/end', [ExamControlController::class, 'endSession'])->name('exam.sessions.end');
        Route::post('sessions/{exam_session}/reset', [ExamControlController::class, 'resetSession'])->name('exam.sessions.reset');
        Route::post('sessions/{exam_session}/compute-scores', [ExamControlController::class, 'computeScores'])->name('exam.sessions.compute-scores');
        Route::get('sessions/{exam_session}/monitor', [ExamControlController::class, 'monitor'])->name('exam.sessions.monitor');
        Route::get('sessions/{exam_session}/export-results', [ExamControlController::class, 'exportResults'])->name('exam.sessions.export-results');
        Route::post('sessions/{exam_session}/release-scores', [ExamControlController::class, 'releaseAllScores'])->name('exam.sessions.release-scores');
        Route::get('sessions/{exam_session}/participants', [ExamParticipantController::class, 'index'])->name('exam.sessions.participants.index');
        Route::post('sessions/{exam_session}/participants', [ExamParticipantController::class, 'store'])->name('exam.sessions.participants.store');
        Route::post('sessions/{exam_session}/participants/generate-numbers', [ExamParticipantController::class, 'generateNumbers'])->name('exam.sessions.participants.generate-numbers');
        Route::put('sessions/{exam_session}/participants/reorder', [ExamParticipantController::class, 'reorder'])->name('exam.sessions.participants.reorder');
        Route::get('sessions/{exam_session}/participants/print-cards', [ExamParticipantController::class, 'printSessionCards'])->name('exam.sessions.participants.print-cards');
        Route::delete('participants/{exam_participant}', [ExamParticipantController::class, 'destroy'])->name('exam.participants.destroy');
        Route::put('participants/{exam_participant}', [ExamParticipantController::class, 'update'])->name('exam.participants.update');
        Route::get('participants/{exam_participant}/answers', [ExamParticipantController::class, 'answers'])->name('exam.participants.answers');
        Route::post('participants/{exam_participant}/recompute', [ExamControlController::class, 'recomputeParticipant'])->name('exam.participants.recompute');
        Route::post('participants/{exam_participant}/reset', [ExamControlController::class, 'resetParticipant'])->name('exam.participants.reset');
        Route::put('answers/{exam_answer}/score', [ExamControlController::class, 'updateAnswerScore'])->name('exam.answers.update-score');
        Route::post('participants/{exam_participant}/regenerate-token', [ExamParticipantController::class, 'regenerateToken'])->name('exam.participants.regenerate-token');
        Route::get('participants/{exam_participant}/print-card', [ExamParticipantController::class, 'printCard'])->name('exam.participants.print-card');
        Route::post('participants/{exam_participant}/release-score', [ExamControlController::class, 'releaseScore'])->name('exam.participants.release-score');
        Route::get('subjects', [SubjectController::class, 'index'])->name('exam.subjects.index');
        Route::get('subjects-ready', [ExamController::class, 'subjectsReady'])->name('exam.subjects.ready');
    });
    Route::middleware(['module:online_exam', 'online_exam.entitled'])->group(function () {
        Route::apiResource('question-stimuli', QuestionStimulusController::class);
        Route::post('banks/restore', [BankSoalController::class, 'restore'])->name('exam.banks.restore');
        Route::get('banks/{bank_soal}/backup', [BankSoalController::class, 'backup'])->name('exam.banks.backup');
        Route::get('banks', [BankSoalController::class, 'index'])->name('exam.banks.index');
        Route::post('banks', [BankSoalController::class, 'store'])->name('exam.banks.store');
        Route::get('banks/{bank_soal}', [BankSoalController::class, 'show'])->name('exam.banks.show');
        Route::put('banks/{bank_soal}', [BankSoalController::class, 'update'])->name('exam.banks.update');
        Route::delete('banks/{bank_soal}', [BankSoalController::class, 'destroy'])->name('exam.banks.destroy');
        Route::get('banks/{bank_soal}/shares', [BankSoalController::class, 'listShares'])->name('exam.banks.shares.index');
        Route::post('banks/{bank_soal}/share', [BankSoalController::class, 'invite'])->name('exam.banks.share.invite');
        Route::delete('banks/{bank_soal}/share/{user_id}', [BankSoalController::class, 'revoke'])->name('exam.banks.share.revoke');
        Route::post('question-assets/upload', [QuestionAssetController::class, 'uploadImage'])->name('exam.question-assets.upload');
        Route::post('question-bank/reorder', [QuestionBankController::class, 'reorder'])->name('exam.question-bank.reorder');
        Route::get('question-bank/import-template', [QuestionBankController::class, 'importTemplate'])->name('exam.question-bank.import-template');
        Route::post('question-bank/import', [QuestionBankController::class, 'import'])->name('exam.question-bank.import');
        Route::post('question-bank/{question_bank}/duplicate', [QuestionBankController::class, 'duplicate'])->name('exam.question-bank.duplicate');
        Route::apiResource('question-bank', QuestionBankController::class);
    });

    // Mitra DU/DI (shared PKL + BKK — Beta, SMK/MAK)
    Route::middleware(['vocational', 'module:pkl|bkk'])->group(function () {
        Route::get('industry-partners', [IndustryPartnerController::class, 'index']);
        Route::post('industry-partners', [IndustryPartnerController::class, 'store']);
        Route::get('industry-partners/{industry_partner}', [IndustryPartnerController::class, 'show']);
        Route::put('industry-partners/{industry_partner}', [IndustryPartnerController::class, 'update']);
        Route::delete('industry-partners/{industry_partner}', [IndustryPartnerController::class, 'destroy']);
    });

    // PKL / Prakerin (Beta, SMK/MAK)
    Route::middleware(['vocational', 'module:pkl'])->group(function () {
        Route::get('pkl/periods', [PklPeriodController::class, 'index']);
        Route::post('pkl/periods', [PklPeriodController::class, 'store']);
        Route::get('pkl/periods/{pkl_period}', [PklPeriodController::class, 'show']);
        Route::put('pkl/periods/{pkl_period}', [PklPeriodController::class, 'update']);
        Route::delete('pkl/periods/{pkl_period}', [PklPeriodController::class, 'destroy']);

        Route::get('pkl/placements/export', [PklPlacementController::class, 'export']);
        Route::post('pkl/placements/bulk', [PklPlacementController::class, 'bulkStore']);
        Route::get('pkl/placements', [PklPlacementController::class, 'index']);
        Route::post('pkl/placements', [PklPlacementController::class, 'store']);
        Route::get('pkl/placements/{pkl_placement}', [PklPlacementController::class, 'show']);
        Route::put('pkl/placements/{pkl_placement}', [PklPlacementController::class, 'update']);
        Route::delete('pkl/placements/{pkl_placement}', [PklPlacementController::class, 'destroy']);
        Route::get('pkl/placements/{pkl_placement}/monitoring-logs', [PklPlacementController::class, 'listMonitoring']);
        Route::post('pkl/placements/{pkl_placement}/monitoring-logs', [PklPlacementController::class, 'storeMonitoring']);
        Route::delete('pkl/placements/{pkl_placement}/monitoring-logs/{monitoring_log}', [PklPlacementController::class, 'destroyMonitoring']);
        Route::get('pkl/placements/{pkl_placement}/journals', [PklJournalController::class, 'indexForPlacement']);
        Route::put('pkl/placements/{pkl_placement}/journals/{journal}', [PklJournalController::class, 'updateSupervisorNotes']);
    });

    // BKK / Bursa Kerja (Beta, SMK/MAK)
    Route::middleware(['vocational', 'module:bkk'])->group(function () {
        Route::get('bkk/vacancies', [BkkVacancyController::class, 'index']);
        Route::post('bkk/vacancies', [BkkVacancyController::class, 'store']);
        Route::get('bkk/vacancies/{bkk_vacancy}', [BkkVacancyController::class, 'show']);
        Route::put('bkk/vacancies/{bkk_vacancy}', [BkkVacancyController::class, 'update']);
        Route::delete('bkk/vacancies/{bkk_vacancy}', [BkkVacancyController::class, 'destroy']);

        Route::get('bkk/applications/export', [BkkApplicationController::class, 'export']);
        Route::get('bkk/applications', [BkkApplicationController::class, 'index']);
        Route::post('bkk/applications', [BkkApplicationController::class, 'store']);
        Route::put('bkk/applications/{bkk_application}', [BkkApplicationController::class, 'update']);
        Route::delete('bkk/applications/{bkk_application}', [BkkApplicationController::class, 'destroy']);
    });
});

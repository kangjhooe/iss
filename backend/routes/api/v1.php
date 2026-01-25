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
    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::get('/permissions/teachers', [PermissionController::class, 'getTeachers']);
    Route::put('/permissions/users/{userId}', [PermissionController::class, 'updateUserPermissions']);

    // Institution routes
    Route::middleware('module:institution')->group(function () {
        Route::get('/institution/my', [InstitutionController::class, 'myInstitution']);
        Route::put('/institution/{id}/active-academic-year', [InstitutionController::class, 'updateActiveAcademicYear'])->name('institution.update-active-academic-year');
        Route::post('/institution/{id}/logo', [InstitutionController::class, 'uploadLogo'])->name('institution.upload-logo');
        Route::get('/institution/{id}/logo', [InstitutionController::class, 'getLogo'])->name('institution.get-logo');
        Route::apiResource('institution', InstitutionController::class);
    });

    // Student routes with caching
    Route::middleware('module:student')->group(function () {
        Route::middleware('cache:300')->group(function () {
            Route::get('/student', [StudentController::class, 'index']);
        });
        Route::get('/student/{id}', [StudentController::class, 'show']);
        Route::post('/student', [StudentController::class, 'store']);
        Route::put('/student/{id}', [StudentController::class, 'update']);
        Route::delete('/student/{id}', [StudentController::class, 'destroy']);
    Route::post('/student/{id}/restore', [StudentController::class, 'restore']);
        Route::post('/student/import', [StudentController::class, 'import'])->name('student.import');
        Route::post('/student/{id}/documents', [StudentController::class, 'uploadDocument'])->name('student.upload-document');
        Route::delete('/student/{id}/documents/{documentId}', [StudentController::class, 'deleteDocument'])->name('student.delete-document');
        Route::get('/student/{id}/documents/{documentId}/download', [StudentController::class, 'downloadDocument'])->name('student.download-document');
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
});

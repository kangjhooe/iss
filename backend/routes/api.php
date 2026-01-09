<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\InstitutionController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\TeacherController;
use Illuminate\Support\Facades\Route;

// API Info route
Route::get('/', function () {
    return response()->json([
        'message' => 'Indonesia Smart School API',
        'version' => '1.0.0',
        'endpoints' => [
            'public' => [
                'POST /api/register' => 'Register new institution',
                'POST /api/login' => 'Login user',
            ],
            'protected' => [
                'POST /api/logout' => 'Logout user',
                'GET /api/me' => 'Get current user',
                'GET /api/institution' => 'List institutions',
                'GET /api/institution/my' => 'Get my institution',
                'GET /api/institution/{id}' => 'Get institution detail',
                'POST /api/institution' => 'Create institution',
                'PUT /api/institution/{id}' => 'Update institution',
                'DELETE /api/institution/{id}' => 'Delete institution',
                'GET /api/student' => 'List students',
                'GET /api/student/{id}' => 'Get student detail',
                'POST /api/student' => 'Create student',
                'PUT /api/student/{id}' => 'Update student',
                'DELETE /api/student/{id}' => 'Delete student',
                'GET /api/teacher' => 'List teachers',
                'GET /api/teacher/{id}' => 'Get teacher detail',
                'POST /api/teacher' => 'Create teacher',
                'PUT /api/teacher/{id}' => 'Update teacher',
                'DELETE /api/teacher/{id}' => 'Delete teacher',
            ],
        ],
        'note' => 'Protected routes require Bearer token in Authorization header',
    ]);
});

// Public routes with rate limiting
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes with rate limiting
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Institution routes
    Route::get('/institution/my', [InstitutionController::class, 'myInstitution']);
    Route::apiResource('institution', InstitutionController::class);

    // Student routes
    Route::apiResource('student', StudentController::class);

    // Teacher routes
    Route::apiResource('teacher', TeacherController::class);
});

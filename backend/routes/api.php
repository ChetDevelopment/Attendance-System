<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Teacher\TeacherAttendanceController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| Admin Routes (Protected)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'Attendance API is working'
    ]);
});

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:admin']) // Only admin users can access
    ->group(function () {

        // User CRUD
        Route::apiResource('users', UserController::class);
        Route::put('users/{id}/role', [UserController::class, 'assignRole']);

        // Student CRUD
        Route::apiResource('students', StudentController::class);

        // Class CRUD
        Route::apiResource('classes', ClassController::class);

        // Academic Year CRUD
        Route::apiResource('academic-years', AcademicYearController::class);
        Route::put('academic-years/{id}/activate', [AcademicYearController::class, 'activate']);


        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::match(['get', 'post'], '/register', [AuthController::class, 'register']);
    Route::match(['get', 'post'], '/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| Attendance Routes (Authenticated users)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('attendances', AttendanceController::class);
    Route::middleware('auth:sanctum')->apiResource('attendances', AttendanceController::class);


    Route::middleware('auth:sanctum')->group(function () {

        Route::get(
            '/teacher/classes/{classId}/students',
            [TeacherAttendanceController::class, 'getStudentsByClass']
        );

        Route::post(
            '/teacher/attendance',
            [TeacherAttendanceController::class, 'submitAttendance']
        );
    });
});
Route::prefix('dashboard')->group(function () {

    Route::get('/present-today', [DashboardController::class, 'presentToday']);
    Route::get('/absent-today', [DashboardController::class, 'absentToday']);
    Route::get('/late-today', [DashboardController::class, 'lateToday']);
});

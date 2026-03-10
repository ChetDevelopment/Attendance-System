<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Teacher\TeacherAttendanceController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Attendance API is working',
    ]);
});

/*
|--------------------------------------------------------------------------
| Public Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard')->group(function () {
    Route::get('/today-attendance', [DashboardController::class, 'todayAttendance']);
    Route::get('/present-today', [DashboardController::class, 'presentToday']);
    Route::get('/absent-today', [DashboardController::class, 'absentToday']);
    Route::get('/late-today', [DashboardController::class, 'lateToday']);
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| Attendance Routes (Authenticated Users)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('attendances', AttendanceController::class);

    // Admin view across teachers/classes
    Route::get('/admin/attendances', [AttendanceController::class, 'adminIndex']);

    // Teacher attendance actions
    Route::post('/attendances/mark-present', [AttendanceController::class, 'markPresent']);
    Route::post('/attendances/mark-absent', [AttendanceController::class, 'markAbsent']);
    Route::post('/attendances/mark-late', [AttendanceController::class, 'markLate']);
    Route::post('/attendances/{attendance}/unlock', [AttendanceController::class, 'unlock']);

    // Student attendance history
    Route::get('/attendance/history', [AttendanceController::class, 'history']);

    Route::get('/teacher/classes/{classId}/students', [TeacherAttendanceController::class, 'getStudentsByClass']);
    Route::post('/teacher/attendance', [TeacherAttendanceController::class, 'submitAttendance']);
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->group(function () {
        Route::get('roles', function () {
            return response()->json(Role::select('id', 'name', 'slug')->orderBy('id')->get());
        });

        Route::apiResource('users', UserController::class);
        Route::put('users/{id}/role', [UserController::class, 'assignRole']);

        Route::apiResource('students', StudentController::class);
        Route::apiResource('classes', ClassController::class);

        Route::apiResource('academic-years', AcademicYearController::class);
        Route::put('academic-years/{id}/activate', [AcademicYearController::class, 'activate']);
    });

<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\TeacherAttendanceController;

Route::prefix('auth')->group(function () {
    // Use POST for register/login for consistency and security
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->apiResource('attendances', AttendanceController::class);


Route::middleware('auth:sanctum')->group(function () {

    // Teacher: Get all students (no class filter)
    Route::get('/teacher/students', [TeacherAttendanceController::class, 'getAllStudents']);

    // Teacher: Get students by class
    Route::get(
        '/teacher/classes/{classId}/students',
        [TeacherAttendanceController::class, 'getStudentsByClass']
    );

    // Teacher: Get schedule/sessions
    Route::get('/teacher/schedule', [TeacherAttendanceController::class, 'getSchedule']);

    // Teacher: Get dashboard data
    Route::get('/teacher/dashboard', [TeacherAttendanceController::class, 'getDashboard']);

    // Teacher: Get justifications/absence requests
    Route::get('/teacher/justifications', [TeacherAttendanceController::class, 'getJustifications']);

    // Teacher: Get attendance history
    Route::get('/teacher/history', [TeacherAttendanceController::class, 'getHistory']);

    // Teacher: Get notifications
    Route::get('/teacher/notifications', [TeacherAttendanceController::class, 'getNotifications']);

    // Teacher: Submit attendance
    Route::post(
        '/teacher/attendance',
        [TeacherAttendanceController::class, 'submitAttendance']
    );
});

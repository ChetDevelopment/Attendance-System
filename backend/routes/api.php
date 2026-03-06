<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Teacher\TeacherAttendanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::match(['get', 'post'], '/register', [AuthController::class, 'register']);
    Route::match(['get', 'post'], '/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('admin/users', UserController::class);
    Route::put('admin/users/{id}/role', [UserController::class, 'assignRole']);

    Route::post('/attendances/submit', [AttendanceController::class, 'submit']);
    Route::apiResource('attendances', AttendanceController::class);

    Route::get(
        '/teacher/classes/{classId}/students',
        [TeacherAttendanceController::class, 'getStudentsByClass']
    );

    Route::post(
        '/teacher/attendance',
        [TeacherAttendanceController::class, 'submitAttendance']
    );
});

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

// Custom attendance actions (keep names consistent with resource route)
Route::middleware('auth:sanctum')->post('/attendances/mark-present', [AttendanceController::class, 'markPresent']);
Route::middleware('auth:sanctum')->post('/attendances/mark-absent', [AttendanceController::class, 'markAbsent']);
Route::middleware('auth:sanctum')->post('/attendances/{attendance}/unlock', [AttendanceController::class, 'unlock']);


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

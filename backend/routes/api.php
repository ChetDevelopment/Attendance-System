<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\TeacherAttendanceController;
use App\Http\Controllers\Student\StudentAttendanceController;

Route::prefix('auth')->group(function () {
    Route::match(['get', 'post'], '/register', [AuthController::class, 'register']);
    Route::match(['get', 'post'], '/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->apiResource('attendances', AttendanceController::class);

Route::middleware('auth:sanctum')->prefix('student/attendance')->group(function () {
    Route::post('/card-scan', [StudentAttendanceController::class, 'cardScan']);
});

// Alias for external devices/integrations (same handler as card-scan).
Route::middleware('auth:sanctum')->post('/receive-card-id', [StudentAttendanceController::class, 'cardScan']);


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

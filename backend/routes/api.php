<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\TeacherAttendanceController;

Route::prefix('auth')->group(function () {
    Route::match(['get', 'post'], '/register', [AuthController::class, 'register']);
    Route::match(['get', 'post'], '/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

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
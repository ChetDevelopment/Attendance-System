<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::match(['get', 'post'], '/login', [AuthController::class, 'login']);

    Route::middleware('auth.jwt')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

Route::prefix('student')->middleware(['auth.jwt', 'role:student'])->group(function () {
    Route::get('/dashboard/stats', [StudentDashboardController::class, 'getStats']);
    Route::get('/attendance/history', [StudentDashboardController::class, 'getHistory']);
    Route::post('/attendance/check-in', [StudentDashboardController::class, 'checkIn']);
    Route::post('/attendance/request', [StudentDashboardController::class, 'requestManual']);
});

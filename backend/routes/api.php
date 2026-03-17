<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\AttendanceRecordController;
use App\Http\Controllers\Admin\SystemMaintenanceController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\AbsenceManagementController;
use App\Http\Controllers\Teacher\TeacherAttendanceController;
use App\Http\Controllers\Student\StudentAttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
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
| Protected Resource Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('attendances', AttendanceController::class);

    // Student specific
    Route::prefix('student/attendance')->group(function () {
        Route::post('/card-scan', [StudentAttendanceController::class, 'cardScan']);
    });
    
    // Alias for external devices
    Route::post('/receive-card-id', [StudentAttendanceController::class, 'cardScan']);

    // Teacher specific
    Route::prefix('teacher')->group(function () {
        Route::get('/classes/{classId}/students', [TeacherAttendanceController::class, 'getStudentsByClass']);
        Route::post('/attendance', [TeacherAttendanceController::class, 'submitAttendance']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin Dashboard & Management Routes
|--------------------------------------------------------------------------
*/

// ADMIN-ONLY ROUTES (admin role required)
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {

    // Dashboard Analytics (Admin Dashboard Main Page)
    Route::get('/dashboard/data', [AdminDashboardController::class, 'getDashboardData']);
    Route::get('/dashboard/quick-stats', [AdminDashboardController::class, 'getQuickStats']);
    Route::get('/dashboard/student-analytics', [AdminDashboardController::class, 'getStudentAnalytics']);
    Route::get('/dashboard/class-analytics', [AdminDashboardController::class, 'getClassAnalytics']);
    Route::get('/dashboard/system-stats', [AdminDashboardController::class, 'getSystemStats']);

    // User Management
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword']);
    Route::apiResource('users', UserController::class);

    // Student Management
    Route::post('students/bulk', [StudentController::class, 'bulkStore']);
    Route::apiResource('students', StudentController::class);
    Route::post('students/{student}/photo', [StudentController::class, 'uploadPhoto']);

    // Class Management
    Route::apiResource('classes', ClassController::class);

    // Academic Year Management
    Route::apiResource('academic-years', AcademicYearController::class);

    // Session Management
    Route::apiResource('sessions', SessionController::class);
    Route::post('/sessions/initialize', [SessionController::class, 'initialize']);
    Route::post('/sessions/{id}/toggle', [SessionController::class, 'toggle']);

    // Attendance Records Admin Panel
    Route::get('attendance-records', [AttendanceRecordController::class, 'index']);
    Route::patch('attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'update']);
    Route::post('attendance-records/{attendanceRecord}/unlock', [AttendanceRecordController::class, 'unlock']);
    Route::post('attendance-records/manual-correction', [AttendanceRecordController::class, 'manualCorrection']);
    Route::get('attendance-sessions', [AttendanceRecordController::class, 'sessions']);

    // System Maintenance
    Route::post('system/clear-cache', [SystemMaintenanceController::class, 'clearCache']);
    Route::get('system/export-config', [SystemMaintenanceController::class, 'exportConfig']);
});

// SHARED ADMIN ROUTES (admin + teacher + education)
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin,teacher,education'])->group(function () {

    // Dashboard View Endpoints (Read-only)
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/overview', [DashboardController::class, 'getOverview']);
    Route::get('/dashboard/late-students', [DashboardController::class, 'lateStudents']);
    Route::get('/dashboard/offsite-students', [DashboardController::class, 'offsiteStudentsToday']);
    Route::get('/dashboard/notifications', [DashboardController::class, 'recentNotifications']);
    Route::get('/dashboard/active-session', [DashboardController::class, 'activeSession']);

    // Absence Management
    Route::get('/absences/stats', [AbsenceManagementController::class, 'stats']);
    Route::get('/absences', [AbsenceManagementController::class, 'index']);
    Route::get('/absences/{id}', [AbsenceManagementController::class, 'show']);
    Route::put('/absences/{id}/reason', [AbsenceManagementController::class, 'updateReason']);
    Route::post('/absences/{id}/comment', [AbsenceManagementController::class, 'addComment']);
    Route::patch('/absences/{id}/status', [AbsenceManagementController::class, 'updateStatus']);
    Route::post('/absences/{id}/follow-up', [AbsenceManagementController::class, 'addFollowUp']);
    Route::get('/absences/student/{studentId}/history', [AbsenceManagementController::class, 'getHistory']);
    Route::post('/absences/bulk-status', [AbsenceManagementController::class, 'bulkUpdateStatus']);

    // View-Only Endpoints
    Route::get('roles', [RoleController::class, 'index']);
});

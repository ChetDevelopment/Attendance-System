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
use App\Http\Controllers\Admin\AbsenceManagementController;
use App\Http\Controllers\Admin\UserProfileController;
use App\Http\Controllers\Admin\PredictionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\TeacherPortalController;
use App\Http\Controllers\Student\StudentDashboardController;
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
        Route::get('/history', [StudentAttendanceController::class, 'history']);
        Route::post('/check-in', [StudentAttendanceController::class, 'submitAttendance']);
        Route::post('/request', [StudentAttendanceController::class, 'requestManual']);
        Route::post('/card-scan', [StudentAttendanceController::class, 'cardScan']);
        Route::post('/fingerprint-scan', [StudentAttendanceController::class, 'fingerprintScan']);
        Route::post('/validate-biometric', [StudentAttendanceController::class, 'validateBiometric']);
        Route::get('/biometric-history', [StudentAttendanceController::class, 'biometricHistory']);
        Route::get('/biometric-status', [StudentAttendanceController::class, 'biometricStatus']);
        Route::post('/student-info', [StudentAttendanceController::class, 'studentInfo']);
    });

    Route::prefix('student')->middleware('role:student')->group(function () {
        Route::get('/dashboard/stats', [StudentDashboardController::class, 'getStats']);
        Route::get('/attendance/history-detailed', [StudentDashboardController::class, 'getHistory']);
    });
    
    // Alias for external devices
    Route::post('/receive-card-id', [StudentAttendanceController::class, 'cardScan']);

    // User Profile
    Route::get('user/profile', [UserProfileController::class, 'show']);
    Route::post('user/profile', [UserProfileController::class, 'updateProfile']);
    Route::post('user/profile/avatar', [UserProfileController::class, 'uploadAvatar']);
    Route::post('user/settings', [UserProfileController::class, 'updateSettings']);

    // Teacher specific
    Route::prefix('teacher')->group(function () {
        Route::get('/dashboard', [TeacherPortalController::class, 'dashboard']);
        Route::get('/schedule', [TeacherPortalController::class, 'schedule']);
        Route::get('/justifications', [TeacherPortalController::class, 'justifications']);
        Route::get('/history', [TeacherPortalController::class, 'history']);
        Route::get('/students', [TeacherPortalController::class, 'students']);
        Route::get('/notifications', [TeacherPortalController::class, 'notifications']);
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
    Route::post('system/clear-dashboard-cache', [SystemMaintenanceController::class, 'clearDashboardCache']);
    Route::get('system/export-config', [SystemMaintenanceController::class, 'exportConfig']);

    // Predictions & Analytics
    Route::get('predictions/at-risk', [PredictionController::class, 'getAtRiskStudents']);
    Route::get('predictions/student/{studentId}', [PredictionController::class, 'getStudentPrediction']);
    Route::get('predictions/insights', [PredictionController::class, 'getInsights']);
    Route::get('predictions/weekly', [PredictionController::class, 'getWeeklyPrediction']);
    Route::get('predictions/historical', [PredictionController::class, 'getHistoricalData']);
    Route::post('predictions/clear-cache', [PredictionController::class, 'clearCache']);

    // Reports
    Route::get('reports/student/{studentId}', [ReportController::class, 'getStudentReport']);
    Route::get('reports/student/{studentId}/month/{month}/year/{year}', [ReportController::class, 'getStudentReportByMonth']);
    Route::get('reports/student/{studentId}/year/{year}', [ReportController::class, 'getStudentReportByYear']);
    Route::get('reports/class/{classId}', [ReportController::class, 'getClassReport']);
    Route::get('reports/class/{classId}/month/{month}/year/{year}', [ReportController::class, 'getClassMonthlySummary']);
    Route::get('reports/class/{classId}/range', [ReportController::class, 'getClassReportByDateRange']);
    Route::get('reports/attendance', [ReportController::class, 'getAttendance']);
    Route::post('reports/clear-cache', [ReportController::class, 'clearCache']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{notificationId}', [NotificationController::class, 'destroy']);
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

    // Risk Students & Trends (from overview)
    Route::get('/students/risk', [DashboardController::class, 'riskStudents']);
    Route::get('/reports/trends', [DashboardController::class, 'trends']);

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

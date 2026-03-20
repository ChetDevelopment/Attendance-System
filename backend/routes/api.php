<?php

use App\Http\Controllers\Admin\AbsenceManagementController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AttendanceRecordController;
use App\Http\Controllers\Admin\BiometricManagementController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EducationDashboardController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PredictionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SystemMaintenanceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserProfileController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Student\StudentAttendanceController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Teacher\TeacherAttendanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->apiResource('attendances', AttendanceController::class);
Route::middleware('auth:sanctum')->post('/attendance/mark', [AttendanceController::class, 'mark']);
Route::middleware('auth:sanctum')->get('/admin/attendances', [AttendanceController::class, 'adminIndex']);
Route::middleware('auth:sanctum')->post('/attendances/mark-present', [AttendanceController::class, 'markPresent']);
Route::middleware('auth:sanctum')->post('/attendances/mark-absent', [AttendanceController::class, 'markAbsent']);
Route::middleware('auth:sanctum')->post('/attendances/mark-late', [AttendanceController::class, 'markLate']);
Route::middleware('auth:sanctum')->post('/attendances/{attendance}/unlock', [AttendanceController::class, 'unlock']);

Route::middleware('auth:sanctum')->group(function () {
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
        Route::get('/attendance/history', [StudentDashboardController::class, 'getHistory']);
        Route::get('/attendance/history-detailed', [StudentDashboardController::class, 'getHistory']);
    });

    Route::post('/receive-card-id', [StudentAttendanceController::class, 'cardScan']);

    Route::prefix('teacher')->group(function () {
        Route::get('/students', [TeacherAttendanceController::class, 'getAllStudents']);
        Route::get('/classes/{classId}/students', [TeacherAttendanceController::class, 'getStudentsByClass']);
        Route::get('/schedule', [TeacherAttendanceController::class, 'getSchedule']);
        Route::get('/dashboard', [TeacherAttendanceController::class, 'getDashboard']);
        Route::get('/today-schedule', [TeacherAttendanceController::class, 'getTodaySchedule']);
        Route::get('/justifications', [TeacherAttendanceController::class, 'getJustifications']);
        Route::get('/history', [TeacherAttendanceController::class, 'getHistory']);
        Route::get('/notifications', [TeacherAttendanceController::class, 'getNotifications']);
        Route::get('/academic-years', [TeacherAttendanceController::class, 'getAcademicYears']);
        Route::post('/attendance', [TeacherAttendanceController::class, 'submitAttendance']);
    });

    Route::get('/user/profile', [UserProfileController::class, 'show']);
    Route::post('/user/profile', [UserProfileController::class, 'updateProfile']);
    Route::post('/user/settings', [UserProfileController::class, 'updateSettings']);
    Route::post('/user/profile/avatar', [UserProfileController::class, 'uploadAvatar']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{notificationId}', [NotificationController::class, 'destroy']);
});

Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/dashboard/data', [AdminDashboardController::class, 'getDashboardData']);
    Route::get('/dashboard/quick-stats', [AdminDashboardController::class, 'getQuickStats']);
    Route::get('/dashboard/student-analytics', [AdminDashboardController::class, 'getStudentAnalytics']);
    Route::get('/dashboard/class-analytics', [AdminDashboardController::class, 'getClassAnalytics']);
    Route::get('/dashboard/system-stats', [AdminDashboardController::class, 'getSystemStats']);

    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword']);
    Route::apiResource('users', UserController::class);

    Route::post('students/bulk', [StudentController::class, 'bulkStore']);
    Route::apiResource('students', StudentController::class);
    Route::post('students/{student}/photo', [StudentController::class, 'uploadPhoto']);

    Route::apiResource('classes', ClassController::class);
    Route::apiResource('academic-years', AcademicYearController::class);

    Route::apiResource('sessions', SessionController::class);
    Route::post('/sessions/initialize', [SessionController::class, 'initialize']);
    Route::post('/sessions/{id}/toggle', [SessionController::class, 'toggle']);

    Route::get('attendance-records', [AttendanceRecordController::class, 'index']);
    Route::patch('attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'update']);
    Route::post('attendance-records/{attendanceRecord}/unlock', [AttendanceRecordController::class, 'unlock']);
    Route::post('attendance-records/manual-correction', [AttendanceRecordController::class, 'manualCorrection']);
    Route::get('attendance-sessions', [AttendanceRecordController::class, 'sessions']);

    Route::post('system/clear-cache', [SystemMaintenanceController::class, 'clearCache']);
    Route::post('system/clear-dashboard-cache', [SystemMaintenanceController::class, 'clearDashboardCache']);
    Route::get('system/export-config', [SystemMaintenanceController::class, 'exportConfig']);
    Route::get('system/backups', [SystemMaintenanceController::class, 'listBackups']);
    Route::post('system/backups', [SystemMaintenanceController::class, 'createBackup']);
    Route::post('system/backups/restore', [SystemMaintenanceController::class, 'restoreBackup']);

    Route::get('predictions/at-risk', [PredictionController::class, 'getAtRiskStudents']);
    Route::get('predictions/student/{studentId}', [PredictionController::class, 'getStudentPrediction']);
    Route::get('predictions/insights', [PredictionController::class, 'getInsights']);
    Route::get('predictions/weekly', [PredictionController::class, 'getWeeklyPrediction']);
    Route::get('predictions/historical', [PredictionController::class, 'getHistoricalData']);
    Route::post('predictions/clear-cache', [PredictionController::class, 'clearCache']);

    Route::get('reports/student/{studentId}', [ReportController::class, 'getStudentReport']);
    Route::get('reports/student/{studentId}/month/{month}/year/{year}', [ReportController::class, 'getStudentReportByMonth']);
    Route::get('reports/student/{studentId}/year/{year}', [ReportController::class, 'getStudentReportByYear']);
    Route::get('reports/class/{classId}', [ReportController::class, 'getClassReport']);
    Route::get('reports/class/{classId}/month/{month}/year/{year}', [ReportController::class, 'getClassMonthlySummary']);
    Route::get('reports/class/{classId}/range', [ReportController::class, 'getClassReportByDateRange']);
    Route::get('reports/attendance', [ReportController::class, 'getAttendance']);
    Route::get('reports/export/student/{studentId}', [ReportController::class, 'exportStudentReport']);
    Route::get('reports/export/class/{classId}', [ReportController::class, 'exportClassReport']);
    Route::get('reports/export/range', [ReportController::class, 'exportByDateRange']);
    Route::post('reports/clear-cache', [ReportController::class, 'clearCache']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{notificationId}', [NotificationController::class, 'destroy']);

    Route::get('biometric/overview', [BiometricManagementController::class, 'overview']);
    Route::get('biometric/students', [BiometricManagementController::class, 'students']);
    Route::get('biometric/students/{student}/history', [BiometricManagementController::class, 'history']);
    Route::patch('biometric/students/{student}', [BiometricManagementController::class, 'update']);

    Route::get('activity-logs', [ActivityLogController::class, 'index']);
});

Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin,teacher,education'])->group(function () {
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/overview', [DashboardController::class, 'getOverview']);
    Route::get('/dashboard/late-students', [DashboardController::class, 'lateStudents']);
    Route::get('/dashboard/offsite-students', [DashboardController::class, 'offsiteStudentsToday']);
    Route::get('/dashboard/notifications', [DashboardController::class, 'recentNotifications']);
    Route::get('/dashboard/active-session', [DashboardController::class, 'activeSession']);

    Route::get('/students/risk', [DashboardController::class, 'riskStudents']);
    Route::get('/reports/trends', [DashboardController::class, 'trends']);

    Route::prefix('education')->group(function () {
        Route::get('/dashboard/stats', [EducationDashboardController::class, 'stats']);
        Route::get('/students/absent-today', [EducationDashboardController::class, 'absentToday']);
        Route::get('/students/all-absent', [EducationDashboardController::class, 'allAbsent']);
        Route::get('/students/risk', [EducationDashboardController::class, 'riskStudents']);
        Route::get('/reports/class-summary', [EducationDashboardController::class, 'classReports']);
        Route::get('/attendance/detail/{id}', [EducationDashboardController::class, 'attendanceDetail']);
        Route::post('/attendance/follow-up', [EducationDashboardController::class, 'submitFollowUp']);
        Route::post('/attendance/alert', [EducationDashboardController::class, 'sendAlert']);
    });

    Route::get('/absences/stats', [AbsenceManagementController::class, 'stats']);
    Route::get('/absences', [AbsenceManagementController::class, 'index']);
    Route::get('/absences/{id}', [AbsenceManagementController::class, 'show']);
    Route::put('/absences/{id}/reason', [AbsenceManagementController::class, 'updateReason']);
    Route::post('/absences/{id}/comment', [AbsenceManagementController::class, 'addComment']);
    Route::patch('/absences/{id}/status', [AbsenceManagementController::class, 'updateStatus']);
    Route::post('/absences/{id}/follow-up', [AbsenceManagementController::class, 'addFollowUp']);
    Route::get('/absences/student/{studentId}/history', [AbsenceManagementController::class, 'getHistory']);
    Route::post('/absences/bulk-status', [AbsenceManagementController::class, 'bulkUpdateStatus']);

    Route::get('roles', [RoleController::class, 'index']);
});

Route::prefix('education')->middleware(['auth:sanctum', 'role:admin,teacher,education'])->group(function () {
    Route::get('/dashboard/stats', [EducationDashboardController::class, 'stats']);
    Route::get('/students/absent-today', [EducationDashboardController::class, 'absentToday']);
    Route::get('/students/all-absent', [EducationDashboardController::class, 'allAbsent']);
    Route::get('/students/risk', [EducationDashboardController::class, 'riskStudents']);
    Route::get('/reports/class-summary', [EducationDashboardController::class, 'classReports']);
    Route::get('/attendance/detail/{id}', [EducationDashboardController::class, 'attendanceDetail']);
    Route::post('/attendance/follow-up', [EducationDashboardController::class, 'submitFollowUp']);
    Route::post('/attendance/alert', [EducationDashboardController::class, 'sendAlert']);
});

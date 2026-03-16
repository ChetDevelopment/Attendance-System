<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\TeacherActivity;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Get comprehensive admin dashboard data
     * Combines all essential dashboard endpoints into one call
     * for better frontend performance.
     */
    public function getDashboardData()
    {
        return Cache::remember('admin_dashboard_complete_v1', 30, function () {
            $todayStart = Carbon::today();
            $todayEnd = $todayStart->copy()->endOfDay();
            $weekStart = Carbon::today()->startOfWeek();
            $monthStart = Carbon::today()->startOfMonth();
            $now = Carbon::now();

            // Get active academic year
            $activeYear = AcademicYear::query()
                ->where('status', 'Current')
                ->select('id', 'name', 'current_term')
                ->first();

            // Calculate attendance statistics
            $dailyStats = $this->getAttendanceStats($todayStart, $todayEnd);
            $weeklyStats = $this->getAttendanceStats($weekStart, $now);
            $monthlyStats = $this->getAttendanceStats($monthStart, $now);

            // Get student counts
            $totalStudents = Student::count();
            $activeStudents = Student::whereHas('user')
                ->whereHasIn('role', ['student'])
                ->count();

            // Get class statistics
            $totalClasses = StudentClass::count();
            $classesWithActiveSessions = StudentClass::whereHas('sessions', function ($q) {
                $q->where('is_active', true);
            })->count();

            // Get user counts by role
            $adminCount = User::whereHas('role', function ($q) {
                $q->where('name', 'admin');
            })->count();

            $teacherCount = User::whereHas('role', function ($q) {
                $q->where('name', 'teacher');
            })->count();

            // Get recent teacher activities
            $recentActivities = TeacherActivity::query()
                ->with('student:id,fullname')
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'action' => $activity->action,
                        'student_name' => $activity->student?->fullname ?? 'N/A',
                        'created_at' => $activity->created_at->toDateTimeString(),
                    ];
                });

            // Get today's late students
            $lateStudents = $this->getLateStudents($todayStart, $todayEnd);

            // Get active session
            $activeSession = $this->getActiveSession($now);

            // Get attendance trends (last 7 days)
            $trends = $this->getAttendanceTrends(7);

            // Get at-risk students (absent 3+ times in 30 days)
            $riskStudents = $this->getAtRiskStudents(30);

            return response()->json([
                'summary' => [
                    'academic_year' => $activeYear ? [
                        'id' => $activeYear->id,
                        'name' => $activeYear->name,
                        'term' => $activeYear->current_term,
                    ] : null,
                    'total_students' => $totalStudents,
                    'active_students' => $activeStudents,
                    'total_classes' => $totalClasses,
                    'total_admins' => $adminCount,
                    'total_teachers' => $teacherCount,
                    'attendance' => [
                        'today' => $dailyStats,
                        'week' => $weeklyStats,
                        'month' => $monthlyStats,
                    ],
                ],
                'active_session' => $activeSession,
                'recent_activities' => $recentActivities,
                'late_students_today' => $lateStudents,
                'trends' => $trends,
                'risk_students' => $riskStudents,
            ]);
        });
    }

    /**
     * Get attendance statistics for a date range
     */
    private function getAttendanceStats(Carbon $start, Carbon $end): array
    {
        $row = DB::table('attendance_records')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) as excused_count
            ")
            ->first();

        return [
            'present' => (int) ($row->present_count ?? 0),
            'absent' => (int) ($row->absent_count ?? 0),
            'late' => (int) ($row->late_count ?? 0),
            'excused' => (int) ($row->excused_count ?? 0),
            'total' => (int) (($row->present_count ?? 0) + ($row->absent_count ?? 0) +
                              ($row->late_count ?? 0) + ($row->excused_count ?? 0)),
            'attendance_rate' => $row->present_count
                ? round(($row->present_count / max(1, $row->present_count + $row->absent_count)) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get students marked as Late today
     */
    private function getLateStudents(Carbon $start, Carbon $end)
    {
        return DB::table('v_admin_attendance_enriched as va')
            ->whereBetween('va.created_at', [$start, $end])
            ->where('va.status', 'Late')
            ->select([
                'va.attendance_id as id',
                'va.student_id',
                'va.student_name as name',
                'va.class_name as class',
                'va.created_time as time',
                'va.status',
            ])
            ->orderByDesc('va.created_at')
            ->limit(20)
            ->get();
    }

    /**
     * Get currently active session
     */
    private function getActiveSession(Carbon $now)
    {
        $session = DB::table('sessions')
            ->where('start_time', '<=', $now->format('H:i:s'))
            ->where('end_time', '>=', $now->format('H:i:s'))
            ->orderBy('start_time')
            ->first();

        if ($session) {
            return [
                'is_active' => true,
                'id' => (int) $session->id,
                'name' => $session->name,
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
            ];
        }

        return [
            'is_active' => false,
            'id' => null,
            'name' => null,
            'start_time' => null,
            'end_time' => null,
        ];
    }

    /**
     * Get attendance trends over specified days
     */
    private function getAttendanceTrends(int $days)
    {
        $startDate = Carbon::today()->subDays($days - 1);
        $endDate = Carbon::today();

        $records = DB::table('attendance_records')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("
                DATE(created_at) as date,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late
            ")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $records->map(function ($row) {
            return [
                'date' => $row->date,
                'present' => (int) $row->present,
                'absent' => (int) $row->absent,
                'late' => (int) $row->late,
            ];
        });
    }

    /**
     * Get students at risk (high absence rate)
     */
    private function getAtRiskStudents(int $days)
    {
        return DB::table('v_admin_attendance_enriched as va')
            ->where('va.status', 'Absent')
            ->where('va.created_at', '>=', Carbon::today()->subDays($days))
            ->groupBy('va.student_id', 'va.student_name', 'va.class_name')
            ->havingRaw('COUNT(*) >= 3')
            ->select([
                'va.student_id',
                'va.student_name as name',
                'va.class_name as class',
                DB::raw('COUNT(*) as absence_count'),
            ])
            ->orderByDesc('absence_count')
            ->limit(20)
            ->get();
    }

    /**
     * Get quick stats for dashboard widgets
     */
    public function getQuickStats()
    {
        return Cache::remember('admin_quick_stats_v1', 60, function () {
            $todayStart = Carbon::today();
            $todayEnd = $todayStart->copy()->endOfDay();

            $todayStats = $this->getAttendanceStats($todayStart, $todayEnd);

            $activeSession = $this->getActiveSession(Carbon::now());

            return response()->json([
                'attendance_today' => $todayStats,
                'active_session' => $activeSession,
                'total_students' => Student::count(),
                'total_classes' => StudentClass::count(),
            ]);
        });
    }

    /**
     * Get student analytics for admin dashboard
     */
    public function getStudentAnalytics()
    {
        return Cache::remember('admin_student_analytics_v1', 120, function () {
            // Students by class
            $studentsByClass = Student::select('class')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('class')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            // Students by generation
            $studentsByGeneration = Student::select('generation')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('generation')
                ->orderByDesc('generation')
                ->get();

            // Biometric enrollment stats
            $enrolledBiometric = Student::where('fingerprint_enrolled', true)->count();
            $totalStudents = Student::count();

            // Students without user accounts
            $studentsWithoutAccounts = Student::whereDoesntHave('user')->count();

            return response()->json([
                'by_class' => $studentsByClass,
                'by_generation' => $studentsByGeneration,
                'biometric_enrollment' => [
                    'enrolled' => $enrolledBiometric,
                    'total' => $totalStudents,
                    'percentage' => $totalStudents > 0
                        ? round(($enrolledBiometric / $totalStudents) * 100, 2)
                        : 0,
                ],
                'without_accounts' => $studentsWithoutAccounts,
            ]);
        });
    }

    /**
     * Get class analytics for admin dashboard
     */
    public function getClassAnalytics()
    {
        return Cache::remember('admin_class_analytics_v1', 120, function () {
            $classes = StudentClass::withCount(['students', 'sessions'])
                ->orderByDesc('id')
                ->get()
                ->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'class_name' => $class->class_name,
                        'student_count' => $class->students_count,
                        'session_count' => $class->sessions_count,
                    ];
                });

            $activeYears = AcademicYear::where('status', 'Current')
                ->with('classes')
                ->get();

            return response()->json([
                'classes' => $classes,
                'active_academic_years' => $activeYears,
            ]);
        });
    }

    /**
     * Get system usage statistics
     */
    public function getSystemStats()
    {
        return Cache::remember('admin_system_stats_v1', 300, function () {
            // Count records by table
            $attendanceRecords = AttendanceRecord::count();
            $sessions = Session::count();
            $teacherActivities = TeacherActivity::count();

            // Get database size info (MySQL specific)
            $dbSize = DB::select("
                SELECT
                    SUM(data_length + index_length) / 1024 / 1024 as size_mb
                FROM information_schema.tables
                WHERE table_schema = '" . env('DB_DATABASE') . "'
            ")[0]->size_mb ?? 0;

            // Get recent activity count (last 24 hours)
            $recentActivity = TeacherActivity::where('created_at', '>=', Carbon::now()->subHours(24))
                ->count();

            return response()->json([
                'database' => [
                    'size_mb' => round($dbSize, 2),
                ],
                'records' => [
                    'attendance' => $attendanceRecords,
                    'sessions' => $sessions,
                    'teacher_activities' => $teacherActivities,
                ],
                'activity' => [
                    'last_24h' => $recentActivity,
                ],
            ]);
        });
    }
}

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
    private const PNC_LAT = 11.5518;
    private const PNC_LNG = 104.9163;
    private const PNC_RADIUS_KM = 0.5;

    // Cache TTL constants (in seconds)
    const CACHE_TTL_SHORT = 60;      // 1 minute for frequently changing data
    const CACHE_TTL_MEDIUM = 300;     // 5 minutes for moderately static data
    const CACHE_TTL_LONG = 600;       // 10 minutes for static data

    /**
     * Get comprehensive admin dashboard data
     * Combines all essential dashboard endpoints into one call
     * for better frontend performance.
     * Optimized with Redis caching and efficient queries.
     */
    public function getDashboardData()
    {
        return Cache::remember('admin_dashboard_complete_v3', self::CACHE_TTL_MEDIUM, function () {
            $todayStart = Carbon::today();
            $weekStart = Carbon::today()->startOfWeek();
            $monthStart = Carbon::today()->startOfMonth();
            $now = Carbon::now();

            // Get active academic year - cached separately for longer
            $activeYear = Cache::remember('admin_active_year', self::CACHE_TTL_LONG, function () {
                return AcademicYear::query()
                    ->where('status', 'Current')
                    ->select('id', 'name', 'current_term')
                    ->first();
            });

            // Single query for all stats
            $allStats = DB::table('attendance_records')
                ->where('created_at', '>=', $monthStart)
                ->selectRaw("
                    SUM(CASE WHEN created_at >= ? AND status = 'present' THEN 1 ELSE 0 END) as today_present,
                    SUM(CASE WHEN created_at >= ? AND status = 'absent' THEN 1 ELSE 0 END) as today_absent,
                    SUM(CASE WHEN created_at >= ? AND status = 'late' THEN 1 ELSE 0 END) as today_late,
                    SUM(CASE WHEN created_at >= ? AND status = 'present' THEN 1 ELSE 0 END) as week_present,
                    SUM(CASE WHEN created_at >= ? AND status = 'absent' THEN 1 ELSE 0 END) as week_absent,
                    SUM(CASE WHEN created_at >= ? AND status = 'late' THEN 1 ELSE 0 END) as week_late,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as month_present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as month_absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as month_late
                ", [$todayStart, $todayStart, $todayStart, $weekStart, $weekStart, $weekStart])
                ->first();

            $dailyStats = $this->formatStats($allStats->today_present, $allStats->today_absent, $allStats->today_late);
            $weeklyStats = $this->formatStats($allStats->week_present, $allStats->week_absent, $allStats->week_late);
            $monthlyStats = $this->formatStats($allStats->month_present, $allStats->month_absent, $allStats->month_late);
            $offsite = $this->countOffsiteBuckets($todayStart, $weekStart, $monthStart);

            // Get counts using single optimized queries with caching
            $counts = Cache::remember('admin_counts_v1', self::CACHE_TTL_MEDIUM, function () {
                return [
                    'total_students' => Student::count(),
                    'active_students' => Student::whereHas('user', function ($q) {
                        $q->whereNotNull('password');
                    })->count(),
                    'total_classes' => StudentClass::count(),
                    'classes_with_sessions' => StudentClass::whereHas('sessions', function ($q) {
                        $q->where('is_active', true);
                    })->count(),
                    'admin_count' => User::whereHas('role', function ($q) {
                        $q->where('name', 'admin');
                    })->count(),
                    'teacher_count' => User::whereHas('role', function ($q) {
                        $q->where('name', 'teacher');
                    })->count(),
                ];
            });

            // Get recent teacher activities with eager loading
            $recentActivities = Cache::remember('admin_recent_activities', self::CACHE_TTL_SHORT, function () {
                return TeacherActivity::query()
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
            });

            // Get today's late students
            $lateStudents = $this->getLateStudents($todayStart, $now->copy()->endOfDay());

            // Get today's off-site students
            $offsiteStudents = $this->getOffsiteStudentsData($todayStart, $now->copy()->endOfDay());

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
                    'total_students' => $counts['total_students'],
                    'active_students' => $counts['active_students'],
                    'total_classes' => $counts['total_classes'],
                    'total_admins' => $counts['admin_count'],
                    'total_teachers' => $counts['teacher_count'],
                    'attendance' => [
                        'today' => $dailyStats,
                        'week' => $weeklyStats,
                        'month' => $monthlyStats,
                    ],
                    'total_present_today' => $dailyStats['present'],
                    'total_absent_today' => $dailyStats['absent'],
                    'total_late_today' => $dailyStats['late'],
                    'total_present_weekly' => $weeklyStats['present'],
                    'total_absent_weekly' => $weeklyStats['absent'],
                    'total_late_weekly' => $weeklyStats['late'],
                    'total_present_monthly' => $monthlyStats['present'],
                    'total_absent_monthly' => $monthlyStats['absent'],
                    'total_late_monthly' => $monthlyStats['late'],
                    'total_offsite_today' => $offsite['today'],
                    'total_offsite_weekly' => $offsite['weekly'],
                    'total_offsite_monthly' => $offsite['monthly'],
                ],
                'active_session' => $activeSession,
                'recent_activities' => $recentActivities,
                'late_students_today' => $lateStudents,
                'offsite_students' => $offsiteStudents,
                'trends' => $trends,
                'risk_students' => $riskStudents,
            ]);
        });
    }

    private function formatStats($present, $absent, $late): array
    {
        $present = (int) $present;
        $absent = (int) $absent;
        $late = (int) $late;
        $total = $present + $absent + $late;

        return [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => 0,
            'total' => $total,
            'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get attendance statistics for a date range
     * Optimized with single query using conditional aggregation
     */
    private function getAttendanceStats(Carbon $start, Carbon $end): array
    {
        // Try to use the view first, fall back to direct table query
        try {
            $row = DB::table('v_admin_attendance_enriched as va')
                ->whereBetween('va.created_at', [$start, $end])
                ->selectRaw("
                    SUM(CASE WHEN va.status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN va.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN va.status = 'late' THEN 1 ELSE 0 END) as late_count
                ")
                ->first();
        } catch (\Exception $e) {
            // Fallback to direct table query
            $row = DB::table('attendance_records')
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
                ")
                ->first();
        }

        $present = (int) ($row->present_count ?? 0);
        $absent = (int) ($row->absent_count ?? 0);
        $late = (int) ($row->late_count ?? 0);
        $total = $present + $absent + $late;

        return [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => 0,
            'total' => $total,
            'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get students marked as Late today
     */
    private function getLateStudents(Carbon $start, Carbon $end)
    {
        try {
            return DB::table('v_admin_attendance_enriched as va')
                ->whereBetween('va.created_at', [$start, $end])
                ->where('va.status', 'late')
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
        } catch (\Exception $e) {
            // Fallback - return empty if view doesn't exist yet
            return collect([]);
        }
    }

    /**
     * Get currently active session
     */
    private function getActiveSession(Carbon $now)
    {
        $cacheKey = 'admin_active_session_' . $now->format('H');

        return Cache::remember($cacheKey, 300, function () use ($now) {
            // Convert to local timezone for comparison with session times
            $localNow = $now->copy()->timezone(config('sessions.timezone', 'Asia/Bangkok'));

            $session = DB::table('sessions')
                ->where('start_time', '<=', $localNow->format('H:i:s'))
                ->where('end_time', '>=', $localNow->format('H:i:s'))
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
        });
    }

    /**
     * Get attendance trends over specified days
     */
    private function getAttendanceTrends(int $days)
    {
        $cacheKey = 'admin_trends_' . $days . 'days';

        return Cache::remember($cacheKey, self::CACHE_TTL_MEDIUM, function () use ($days) {
            $startDate = Carbon::today()->subDays($days - 1);
            $endDate = Carbon::today()->endOfDay();

            $records = DB::table('attendance_records')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("
                    DATE(created_at) as date,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                ")
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $recordMap = $records->keyBy('date');

            return collect(range(0, $days - 1))->map(function ($offset) use ($startDate, $recordMap) {
                $date = $startDate->copy()->addDays($offset);
                $row = $recordMap->get($date->toDateString());

                return [
                    'name' => $date->format('M d'),
                    'value' => (int) ($row->absent ?? 0),
                ];
            })->values();
        });
    }

    /**
     * Get students at risk (high absence rate)
     */
    private function getAtRiskStudents(int $days)
    {
        $cacheKey = 'admin_risk_students_' . $days . 'days';

        return Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($days) {
            try {
                return DB::table('v_admin_attendance_enriched as va')
                    ->where('va.status', 'absent')
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
            } catch (\Exception $e) {
                // Fallback - return empty if view doesn't exist yet
                return collect([]);
            }
        });
    }

    private function getOffsiteStudentsData(Carbon $start, Carbon $end)
    {
        try {
            $pncLat = self::PNC_LAT;
            $pncLng = self::PNC_LNG;
            $radiusKm = self::PNC_RADIUS_KM;

            $rows = DB::table('v_admin_attendance_enriched as va')
                ->whereBetween('va.created_at', [$start, $end])
                ->whereIn('va.status', ['present', 'late', 'excused'])
                ->whereNotNull('va.location')
                ->whereRaw("
                    (6371 * acos(
                        cos(radians({$pncLat})) *
                        cos(radians(JSON_UNQUOTE(JSON_EXTRACT(va.location, '$.lat')))) *
                        cos(radians(JSON_UNQUOTE(JSON_EXTRACT(va.location, '$.lng'))) - radians({$pncLng})) +
                        sin(radians({$pncLat})) *
                        sin(radians(JSON_UNQUOTE(JSON_EXTRACT(va.location, '$.lat'))))
                    )) > {$radiusKm}
                ")
                ->select([
                    'va.attendance_id as id',
                    'va.location',
                    'va.created_at',
                    'va.student_name as name',
                    'va.class_name as class_name',
                    'va.status',
                    DB::raw("(6371 * acos(
                        cos(radians({$pncLat})) *
                        cos(radians(JSON_UNQUOTE(JSON_EXTRACT(va.location, '$.lat')))) *
                        cos(radians(JSON_UNQUOTE(JSON_EXTRACT(va.location, '$.lng'))) - radians({$pncLng})) +
                        sin(radians({$pncLat})) *
                        sin(radians(JSON_UNQUOTE(JSON_EXTRACT(va.location, '$.lat'))))
                    )) as distance_km"),
                ])
                ->orderByDesc('va.created_at')
                ->limit(20)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }

        return $rows->map(function ($row) {
            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'class' => (string) $row->class_name,
                'status' => (string) $row->status,
                'location' => (string) $row->location,
                'distance_km' => round($row->distance_km ?? 0, 3),
                'check_in_time' => Carbon::parse($row->created_at)->format('H:i:s'),
            ];
        });
    }

    private function countOffsiteBuckets(Carbon $todayStart, Carbon $weekStart, Carbon $monthStart): array
    {
        $pncLat = self::PNC_LAT;
        $pncLng = self::PNC_LNG;
        $radiusKm = self::PNC_RADIUS_KM;

        $haversineFormula = "
            (6371 * acos(
                cos(radians({$pncLat})) *
                cos(radians(JSON_UNQUOTE(JSON_EXTRACT(location, '$.lat')))) *
                cos(radians(JSON_UNQUOTE(JSON_EXTRACT(location, '$.lng'))) - radians({$pncLng})) +
                sin(radians({$pncLat})) *
                sin(radians(JSON_UNQUOTE(JSON_EXTRACT(location, '$.lat'))))
            ))
        ";

        $result = DB::table('attendance_records')
            ->where('created_at', '>=', $monthStart)
            ->whereIn('status', ['present', 'late', 'excused'])
            ->whereNotNull('location')
            ->selectRaw("
                SUM(CASE WHEN created_at >= ? AND {$haversineFormula} > ? THEN 1 ELSE 0 END) as today_offsite,
                SUM(CASE WHEN created_at >= ? AND {$haversineFormula} > ? THEN 1 ELSE 0 END) as week_offsite,
                SUM(CASE WHEN {$haversineFormula} > ? THEN 1 ELSE 0 END) as month_offsite
            ", [$todayStart, $radiusKm, $weekStart, $radiusKm, $radiusKm])
            ->first();

        return [
            'today' => (int) ($result->today_offsite ?? 0),
            'weekly' => (int) ($result->week_offsite ?? 0),
            'monthly' => (int) ($result->month_offsite ?? 0),
        ];
    }

    /**
     * Get quick stats for dashboard widgets
     */
    public function getQuickStats()
    {
        return Cache::remember('admin_quick_stats_v2', self::CACHE_TTL_SHORT, function () {
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
        return Cache::remember('admin_student_analytics_v2', self::CACHE_TTL_LONG, function () {
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
        return Cache::remember('admin_class_analytics_v2', self::CACHE_TTL_LONG, function () {
            $classes = StudentClass::withCount(['students', 'sessions'])
                ->orderByDesc('id')
                ->get()
                ->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'class_name' => $class->class_name ?? $class->name,
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
        return Cache::remember('admin_system_stats_v2', self::CACHE_TTL_LONG, function () {
            // Count records by table
            $attendanceRecords = AttendanceRecord::count();
            $sessions = Session::count();
            $teacherActivities = TeacherActivity::count();

            // Get database size info (MySQL specific)
            try {
                $dbSize = DB::select("
                    SELECT
                        SUM(data_length + index_length) / 1024 / 1024 as size_mb
                    FROM information_schema.tables
                    WHERE table_schema = '" . env('DB_DATABASE') . "'
                ")[0]->size_mb ?? 0;
            } catch (\Exception $e) {
                $dbSize = 0;
            }

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

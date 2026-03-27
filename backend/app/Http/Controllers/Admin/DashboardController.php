<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\TeacherActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const PNC_LAT = 11.5518;
    private const PNC_LNG = 104.9163;
    private const PNC_RADIUS_KM = 0.5;

    // Cache TTL constants
    const CACHE_TTL_SHORT = 60;      // 1 minute
    const CACHE_TTL_MEDIUM = 300;    // 5 minutes

    /**
     * Get combined dashboard overview in a single request
     * Performance optimization to reduce frontend round trips
     * Optimized with Redis caching and efficient queries
     */
    public function getOverview()
    {
        return Cache::remember('admin_dashboard_overview_v3', self::CACHE_TTL_MEDIUM, function () {
            $todayStart = Carbon::today();
            $weekStart = Carbon::today()->startOfWeek();
            $monthStart = Carbon::today()->startOfMonth();
            $now = Carbon::now();

            // Get active academic year - cached separately
            $activeYear = Cache::remember('admin_active_year_overview', self::CACHE_TTL_MEDIUM, function () {
                return AcademicYear::query()
                    ->where('status', 'Current')
                    ->select('id', 'name', 'current_term')
                    ->first();
            });

            // Single query to get all attendance counts
            $counts = DB::table('attendance_records')
                ->where('created_at', '>=', $monthStart)
                ->selectRaw("
                    SUM(CASE WHEN created_at >= ? THEN (CASE WHEN status = 'present' THEN 1 ELSE 0 END) ELSE 0 END) as today_present,
                    SUM(CASE WHEN created_at >= ? THEN (CASE WHEN status = 'absent' THEN 1 ELSE 0 END) ELSE 0 END) as today_absent,
                    SUM(CASE WHEN created_at >= ? THEN (CASE WHEN status = 'late' THEN 1 ELSE 0 END) ELSE 0 END) as today_late,
                    SUM(CASE WHEN created_at >= ? THEN (CASE WHEN status = 'present' THEN 1 ELSE 0 END) ELSE 0 END) as week_present,
                    SUM(CASE WHEN created_at >= ? THEN (CASE WHEN status = 'absent' THEN 1 ELSE 0 END) ELSE 0 END) as week_absent,
                    SUM(CASE WHEN created_at >= ? THEN (CASE WHEN status = 'late' THEN 1 ELSE 0 END) ELSE 0 END) as week_late,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as month_present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as month_absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as month_late
                ", [$todayStart, $todayStart, $todayStart, $weekStart, $weekStart, $weekStart])
                ->first();

            $offsite = $this->countOffsiteBuckets($todayStart, $weekStart, $monthStart, $now);

            // Get late students for today
            $lateStudents = $this->getLateStudents($todayStart, $now->copy()->endOfDay());

            // Get offsite students for today
            $offsiteStudents = $this->getOffsiteStudentsData($todayStart, $now->copy()->endOfDay());

            // Get active session
            $session = $this->getActiveSession($now);

            // Get trends data (absences by week)
            $trendData = $this->getAbsenceTrends();

            // Get risk students
            $riskStudents = $this->getRiskStudents();

            return response()->json([
                'summary' => [
                    'total_present_today' => (int) ($counts->today_present ?? 0),
                    'total_absent_today' => (int) ($counts->today_absent ?? 0),
                    'total_late_today' => (int) ($counts->today_late ?? 0),
                    'total_present_weekly' => (int) ($counts->week_present ?? 0),
                    'total_absent_weekly' => (int) ($counts->week_absent ?? 0),
                    'total_late_weekly' => (int) ($counts->week_late ?? 0),
                    'total_present_monthly' => (int) ($counts->month_present ?? 0),
                    'total_absent_monthly' => (int) ($counts->month_absent ?? 0),
                    'total_late_monthly' => (int) ($counts->month_late ?? 0),
                    'total_offsite_today' => $offsite['today'],
                    'total_offsite_weekly' => $offsite['weekly'],
                    'total_offsite_monthly' => $offsite['monthly'],
                    'active_academic_year' => $activeYear ? [
                        'id' => $activeYear->id,
                        'name' => $activeYear->name,
                        'current_term' => $activeYear->current_term,
                    ] : null,
                ],
                'late_students' => $lateStudents,
                'offsite_students' => $offsiteStudents,
                'active_session' => $session,
                'trends' => $trendData,
                'risk_students' => $riskStudents,
            ]);
        });
    }

    public function summary()
    {
        return Cache::remember('admin_dashboard_summary_v2', self::CACHE_TTL_MEDIUM, function () {
            $todayStart = Carbon::today();
            $todayEnd = $todayStart->copy()->endOfDay();
            $weekStart = Carbon::today()->startOfWeek();
            $monthStart = Carbon::today()->startOfMonth();
            $now = Carbon::now();

            $activeYear = Cache::remember('admin_active_year_summary', self::CACHE_TTL_MEDIUM, function () {
                return AcademicYear::query()
                    ->where('status', 'Current')
                    ->select('id', 'name', 'current_term')
                    ->first();
            });

            $daily = $this->countStatusesByRange($todayStart, $todayEnd);
            $weekly = $this->countStatusesByRange($weekStart, $now);
            $monthly = $this->countStatusesByRange($monthStart, $now);
            $offsite = $this->countOffsiteBuckets($todayStart, $weekStart, $monthStart, $now);

            return response()->json([
                'total_present_today' => $daily['present'],
                'total_absent_today' => $daily['absent'],
                'total_late_today' => $daily['late'],
                'total_present_weekly' => $weekly['present'],
                'total_absent_weekly' => $weekly['absent'],
                'total_late_weekly' => $weekly['late'],
                'total_present_monthly' => $monthly['present'],
                'total_absent_monthly' => $monthly['absent'],
                'total_late_monthly' => $monthly['late'],
                'total_offsite_today' => $offsite['today'],
                'total_offsite_weekly' => $offsite['weekly'],
                'total_offsite_monthly' => $offsite['monthly'],
                'active_academic_year' => $activeYear ? [
                    'id' => $activeYear->id,
                    'name' => $activeYear->name,
                    'current_term' => $activeYear->current_term,
                ] : null,
            ]);
        });
    }

    public function lateStudents()
    {
        $today = Carbon::today()->toDateString();
        $cacheKey = "admin_late_students_{$today}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () {
            $todayStart = Carbon::today();
            $todayEnd = Carbon::today()->endOfDay();

            try {
                $rows = DB::table('v_admin_attendance_enriched as va')
                    ->whereBetween('va.created_at', [$todayStart, $todayEnd])
                    ->where('va.status', 'late')
                    ->select([
                        'va.attendance_id as id',
                        'va.student_name as name',
                        'va.class_name as class',
                        'va.created_time as time',
                        'va.status',
                    ])
                    ->orderByDesc('va.created_at')
                    ->get();
            } catch (\Exception $e) {
                // Fallback if view doesn't exist
                $rows = collect([]);
            }

            return response()->json($rows);
        });
    }

    public function recentNotifications()
    {
        return Cache::remember('admin_notifications_v2', self::CACHE_TTL_SHORT, function () {
            $items = TeacherActivity::query()
                ->with('student:id,fullname')
                ->latest()
                ->limit(5)
                ->get()
                ->map(function (TeacherActivity $activity) {
                    return [
                        'id' => $activity->id,
                        'title' => $activity->action,
                        'subtitle' => $activity->student?->fullname
                            ? 'Student: ' . $activity->student->fullname
                            : 'Attendance activity update',
                        'type' => 'activity',
                        'created_at' => optional($activity->created_at)->toDateTimeString(),
                    ];
                });

            return response()->json($items);
        });
    }

    public function activeSession()
    {
        $now = Carbon::now()->timezone(config('sessions.timezone', 'Asia/Bangkok'))->format('H:i:s');
        $cacheKey = 'admin_active_session_check';

        return Cache::remember($cacheKey, 60, function () use ($now) {
            $session = DB::table('sessions')
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now)
                ->orderBy('start_time')
                ->first();

            if (! $session) {
                return response()->json([
                    'is_active' => false,
                    'name' => null,
                    'start_time' => null,
                    'end_time' => null,
                ]);
            }

            return response()->json([
                'is_active' => true,
                'name' => $session->name,
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
            ]);
        });
    }

    /**
     * Students with attendance marked outside PNC geofence today.
     */
    public function offsiteStudentsToday()
    {
        $today = Carbon::today()->toDateString();
        $cacheKey = "admin_offsite_today_{$today}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () {
            $todayStart = Carbon::today();
            $todayEnd = Carbon::today()->endOfDay();
            return response()->json($this->getOffsiteStudentsData($todayStart, $todayEnd));
        });
    }

    /**
     * Get active session (internal helper)
     */
    private function getActiveSession(Carbon $now)
    {
        // Convert to local timezone for comparison with session times
        $localNow = $now->copy()->timezone(config('sessions.timezone', 'Asia/Bangkok'));
        
        $session = DB::table('sessions')
            ->where('start_time', '<=', $localNow->format('H:i:s'))
            ->where('end_time', '>=', $localNow->format('H:i:s'))
            ->orderBy('start_time')
            ->first();

        if (! $session) {
            return [
                'is_active' => false,
                'name' => null,
                'start_time' => null,
                'end_time' => null,
            ];
        }

        return [
            'is_active' => true,
            'name' => $session->name,
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
        ];
    }

    /**
     * Get late students (internal helper)
     */
    private function getLateStudents(Carbon $start, Carbon $end)
    {
        try {
            return DB::table('v_admin_attendance_enriched as va')
                ->whereBetween('va.created_at', [$start, $end])
                ->where('va.status', 'late')
                ->select([
                    'va.attendance_id as id',
                    'va.student_name as name',
                    'va.class_name as class',
                    'va.created_time as time',
                    'va.status',
                ])
                ->orderByDesc('va.created_at')
                ->limit(20)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get risk students (internal helper)
     */
    public function riskStudents()
    {
        try {
            $riskStudents = DB::table('v_admin_attendance_enriched as va')
                ->where('va.status', 'absent')
                ->where('va.created_at', '>=', Carbon::today()->subDays(30))
                ->groupBy('va.student_id', 'va.student_name', 'va.class_name')
                ->havingRaw('COUNT(*) >= 3')
                ->select([
                    'va.student_name as name',
                    'va.class_name as class',
                    DB::raw('COUNT(*) as absence_count'),
                ])
                ->orderByDesc('absence_count')
                ->limit(20)
                ->get();

            return response()->json($riskStudents);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get absence trends (internal helper)
     */
    public function trends()
    {
        $trendsStart = Carbon::today()->startOfMonth();
        $trendsEnd = Carbon::today()->copy()->endOfMonth();
        
        try {
            $trendRows = DB::table('attendance_records')
                ->where('status', 'absent')
                ->whereBetween('created_at', [$trendsStart, $trendsEnd])
                ->selectRaw("FLOOR((DAY(created_at)-1)/7)+1 as week_no, COUNT(*) as value")
                ->groupBy('week_no')
                ->pluck('value', 'week_no');
        } catch (\Exception $e) {
            $trendRows = collect([]);
        }

        $trends = collect(range(1, 4))->map(function (int $week) use ($trendRows) {
            return [
                'name' => 'W' . $week,
                'value' => (int) ($trendRows[$week] ?? 0),
            ];
        })->values();

        return response()->json($trends);
    }

    private function getRiskStudents()
    {
        try {
            return DB::table('v_admin_attendance_enriched as va')
                ->where('va.status', 'absent')
                ->where('va.created_at', '>=', Carbon::today()->subDays(30))
                ->groupBy('va.student_id', 'va.student_name', 'va.class_name')
                ->havingRaw('COUNT(*) >= 3')
                ->select([
                    'va.student_name as name',
                    'va.class_name as class',
                    DB::raw('COUNT(*) as absence_count'),
                ])
                ->orderByDesc('absence_count')
                ->limit(20)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get absence trends (internal helper)
     */
    private function getAbsenceTrends()
    {
        $trendsStart = Carbon::today()->startOfMonth();
        $trendsEnd = Carbon::today()->copy()->endOfMonth();
        
        try {
            $trendRows = DB::table('attendance_records')
                ->where('status', 'absent')
                ->whereBetween('created_at', [$trendsStart, $trendsEnd])
                ->selectRaw("FLOOR((DAY(created_at)-1)/7)+1 as week_no, COUNT(*) as value")
                ->groupBy('week_no')
                ->pluck('value', 'week_no');
        } catch (\Exception $e) {
            $trendRows = collect([]);
        }

        return collect(range(1, 4))->map(function (int $week) use ($trendRows) {
            return [
                'name' => 'W' . $week,
                'value' => (int) ($trendRows[$week] ?? 0),
            ];
        })->values();
    }

    private function getOffsiteStudentsData(Carbon $start, Carbon $end)
    {
        try {
            $pncLat = self::PNC_LAT;
            $pncLng = self::PNC_LNG;
            $radiusKm = self::PNC_RADIUS_KM;
            
            // Re-using the logic from countOffsiteBuckets but as a query for rows
            $haversineCondition = "
                (6371 * acos(
                    cos(radians({$pncLat})) * 
                    cos(radians(JSON_UNQUOTE(JSON_EXTRACT(location, '$.lat')))) * 
                    cos(radians(JSON_UNQUOTE(JSON_EXTRACT(location, '$.lng'))) - radians({$pncLng})) + 
                    sin(radians({$pncLat})) * 
                    sin(radians(JSON_UNQUOTE(JSON_EXTRACT(location, '$.lat'))))
                )) > {$radiusKm}
            ";

            $rows = DB::table('v_admin_attendance_enriched as va')
                ->whereBetween('va.created_at', [$start, $end])
                ->whereIn('va.status', ['present', 'late', 'excused'])
                ->whereNotNull('va.location')
                ->whereRaw($haversineCondition)
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
                    )) as distance_km")
                ])
                ->orderByDesc('va.created_at')
                ->get();
        } catch (\Exception $e) {
            \Log::error('Offsite students query failed: ' . $e->getMessage());
            return [];
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

    private function countStatusesByRange(Carbon $start, Carbon $end): array
    {
        $row = DB::table('attendance_records')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
            ")
            ->first();

        return [
            'present' => (int) ($row->present_count ?? 0),
            'absent' => (int) ($row->absent_count ?? 0),
            'late' => (int) ($row->late_count ?? 0),
        ];
    }

    private function countOffsiteBuckets(Carbon $todayStart, Carbon $weekStart, Carbon $monthStart, Carbon $end): array
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
     * Build Haversine formula condition for SQL queries
     * Assumes location is stored as JSON { "lat": ..., "lng": ... }
     */
    private function buildHaversineCondition(): string
    {
        return "
            (6371 * acos(
                cos(radians(?)) * 
                cos(radians(JSON_UNQUOTE(JSON_EXTRACT(location, '$.lat')))) * 
                cos(radians(JSON_UNQUOTE(JSON_EXTRACT(location, '$.lng'))) - radians(?)) + 
                sin(radians(?)) * 
                sin(radians(JSON_UNQUOTE(JSON_EXTRACT(location, '$.lat'))))
            )) > ?
        ";
    }

    /**
     * Parse coordinates from location string.
     */
    private function extractCoordinates(string $location): ?array
    {
        $location = trim($location);
        if ($location === '') {
            return null;
        }

        $decoded = json_decode($location, true);
        if (is_array($decoded) && isset($decoded['lat'], $decoded['lng'])) {
            return [(float) $decoded['lat'], (float) $decoded['lng']];
        }

        if (preg_match('/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/', $location, $matches)) {
            return [(float) $matches[1], (float) $matches[2]];
        }

        return null;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}



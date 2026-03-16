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

    /**
     * Get combined dashboard overview in a single request
     * Performance optimization to reduce frontend round trips
     */
    public function getOverview()
    {
        return Cache::remember('admin_dashboard_overview_v1', 30, function () {
            $todayStart = Carbon::today();
            $todayEnd = $todayStart->copy()->endOfDay();
            $weekStart = Carbon::today()->startOfWeek();
            $monthStart = Carbon::today()->startOfMonth();
            $now = Carbon::now();

            $activeYear = AcademicYear::query()
                ->where('status', 'Current')
                ->select('id', 'name', 'current_term')
                ->first();

            $daily = $this->countStatusesByRange($todayStart, $todayEnd);
            $weekly = $this->countStatusesByRange($weekStart, $now);
            $monthly = $this->countStatusesByRange($monthStart, $now);
            $offsite = $this->countOffsiteBuckets($todayStart, $weekStart, $monthStart, $now);

            // Get late students for today
            $lateStudents = DB::table('v_admin_attendance_enriched as va')
                ->whereBetween('va.created_at', [$todayStart, $todayEnd])
                ->where('va.status', 'Late')
                ->select([
                    'va.attendance_id as id',
                    'va.student_name as name',
                    'va.class_name as class',
                    'va.created_time as time',
                    'va.status',
                ])
                ->orderByDesc('va.created_at')
                ->get();

            // Get offsite students for today
            $offsiteStudents = $this->getOffsiteStudentsData($todayStart, $todayEnd);

            // Get active session
            $session = DB::table('sessions')
                ->where('start_time', '<=', $now->format('H:i:s'))
                ->where('end_time', '>=', $now->format('H:i:s'))
                ->orderBy('start_time')
                ->first();

            // Get trends data (absences by week)
            $trendsStart = Carbon::today()->startOfMonth();
            $trendsEnd = Carbon::today()->copy()->endOfMonth();
            $trendRows = DB::table('attendance_records')
                ->where('status', 'Absent')
                ->whereBetween('created_at', [$trendsStart, $trendsEnd])
                ->selectRaw("FLOOR((DAY(created_at)-1)/7)+1 as week_no, COUNT(*) as value")
                ->groupBy('week_no')
                ->pluck('value', 'week_no');

            $trendData = collect(range(1, 4))->map(function (int $week) use ($trendRows) {
                return [
                    'name' => 'W' . $week,
                    'value' => (int) ($trendRows[$week] ?? 0),
                ];
            })->values();

            // Get risk students (absent 3+ times in last 30 days)
            $riskStudents = DB::table('v_admin_attendance_enriched as va')
                ->where('va.status', 'Absent')
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

            return response()->json([
                'summary' => [
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
                ],
                'late_students' => $lateStudents,
                'offsite_students' => $offsiteStudents,
                'active_session' => $session ? [
                    'is_active' => true,
                    'name' => $session->name,
                    'start_time' => $session->start_time,
                    'end_time' => $session->end_time,
                ] : [
                    'is_active' => false,
                    'name' => null,
                    'start_time' => null,
                    'end_time' => null,
                ],
                'trends' => $trendData,
                'risk_students' => $riskStudents,
            ]);
        });
    }

    public function summary()
    {
        return Cache::remember('admin_dashboard_summary_v1', 30, function () {
            $todayStart = Carbon::today();
            $todayEnd = $todayStart->copy()->endOfDay();
            $weekStart = Carbon::today()->startOfWeek();
            $monthStart = Carbon::today()->startOfMonth();
            $now = Carbon::now();

            $activeYear = AcademicYear::query()
                ->where('status', 'Current')
                ->select('id', 'name', 'current_term')
                ->first();

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
        $cacheKey = "admin_dashboard_late_students_{$today}";

        return Cache::remember($cacheKey, 30, function () {
            $todayStart = Carbon::today();
            $todayEnd = Carbon::today()->endOfDay();

            $rows = DB::table('v_admin_attendance_enriched as va')
                ->whereBetween('va.created_at', [$todayStart, $todayEnd])
                ->where('va.status', 'Late')
                ->select([
                    'va.attendance_id as id',
                    'va.student_name as name',
                    'va.class_name as class',
                    'va.created_time as time',
                    'va.status',
                ])
                ->orderByDesc('va.created_at')
                ->get();

            return response()->json($rows);
        });
    }

    public function recentNotifications()
    {
        return Cache::remember('admin_dashboard_notifications_v1', 30, function () {
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
        $now = Carbon::now()->format('H:i:s');

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
    }

    /**
     * Students with attendance marked outside PNC geofence today.
     */
    public function offsiteStudentsToday()
    {
        $today = Carbon::today()->toDateString();
        $cacheKey = "admin_dashboard_offsite_today_{$today}";

        return Cache::remember($cacheKey, 30, function () {
            $todayStart = Carbon::today();
            $todayEnd = Carbon::today()->endOfDay();
            return response()->json($this->getOffsiteStudentsData($todayStart, $todayEnd));
        });
    }

    private function getOffsiteStudentsData(Carbon $start, Carbon $end)
    {
        $rows = DB::table('v_admin_attendance_enriched as va')
            ->whereBetween('va.created_at', [$start, $end])
            ->whereIn('va.status', ['Present', 'Late', 'Excused'])
            ->whereNotNull('va.location')
            ->select([
                'va.attendance_id as id',
                'va.location',
                'va.created_at',
                'va.student_name as name',
                'va.class_name as class_name',
                'va.status',
            ])
            ->orderByDesc('va.created_at')
            ->get();

        return $rows->map(function ($row) {
            $coords = $this->extractCoordinates((string) $row->location);
            if ($coords === null) {
                return null;
            }

            [$lat, $lng] = $coords;
            $distanceKm = $this->distanceKm(self::PNC_LAT, self::PNC_LNG, $lat, $lng);
            if ($distanceKm <= self::PNC_RADIUS_KM) {
                return null;
            }

            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'class' => (string) $row->class_name,
                'status' => (string) $row->status,
                'location' => (string) $row->location,
                'distance_km' => round($distanceKm, 3),
                'check_in_time' => Carbon::parse($row->created_at)->format('H:i:s'),
            ];
        })->filter()->values();
    }

    private function countStatusesByRange(Carbon $start, Carbon $end): array
    {
        $row = DB::table('attendance_records')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count
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
        // Use SQL-based Haversine formula for much better performance
        // This avoids loading all records into PHP memory
        
        $pncLat = self::PNC_LAT;
        $pncLng = self::PNC_LNG;
        $radiusKm = self::PNC_RADIUS_KM;
        
        // Haversine formula in SQL (calculates distance in km)
        $haversineSql = "
            (6371 * acos(
                cos(radians(?)) * cos(radians(
                    CASE 
                        WHEN location LIKE '{\"lat\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lat\":', -1), ',', 1)
                        WHEN location LIKE '%\"latitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"latitude\":', -1), ',', 1)
                        ELSE SUBSTRING_INDEX(location, ',', 1)
                    END
                )) * sin(radians(
                    CASE 
                        WHEN location LIKE '{\"lng\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lng\":', -1), '}', 1)
                        WHEN location LIKE '%\"longitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"longitude\":', -1), ',', 1)
                        ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(location, ',', -1), '}', 1)
                    END
                )) - cos(radians(?)) * cos(radians(
                    CASE 
                        WHEN location LIKE '{\"lat\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lat\":', -1), ',', 1)
                        WHEN location LIKE '%\"latitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"latitude\":', -1), ',', 1)
                        ELSE SUBSTRING_INDEX(location, ',', 1)
                    END
                )) * sin(radians(
                    CASE 
                        WHEN location LIKE '{\"lng\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lng\":', -1), '}', 1)
                        WHEN location LIKE '%\"longitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"longitude\":', -1), ',', 1)
                        ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(location, ',', -1), '}', 1)
                    END
                ))
            )) AS distance
        ";

        // Monthly count (outside radius)
        $monthlyResult = DB::table('attendance_records')
            ->whereBetween('created_at', [$monthStart, $end])
            ->whereIn('status', ['Present', 'Late', 'Excused'])
            ->whereNotNull('location')
            ->whereRaw("(
                6371 * acos(
                    cos(radians(?)) * cos(radians(
                        CASE 
                            WHEN location LIKE '{\"lat\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lat\":', -1), ',', 1)
                            WHEN location LIKE '%\"latitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"latitude\":', -1), ',', 1)
                            ELSE SUBSTRING_INDEX(location, ',', 1)
                        END
                    )) * sin(radians(
                        CASE 
                            WHEN location LIKE '{\"lng\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lng\":', -1), '}', 1)
                            WHEN location LIKE '%\"longitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"longitude\":', -1), ',', 1)
                            ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(location, ',', -1), '}', 1)
                        END
                    )) - cos(radians(?)) * cos(radians(
                        CASE 
                            WHEN location LIKE '{\"lat\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lat\":', -1), ',', 1)
                            WHEN location LIKE '%\"latitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"latitude\":', -1), ',', 1)
                            ELSE SUBSTRING_INDEX(location, ',', 1)
                        END
                    )) * sin(radians(
                        CASE 
                            WHEN location LIKE '{\"lng\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lng\":', -1), '}', 1)
                            WHEN location LIKE '%\"longitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"longitude\":', -1), ',', 1)
                            ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(location, ',', -1), '}', 1)
                        END
                    ))
                )
            ) > ?", [$pncLat, $pncLng, $radiusKm])
            ->count();

        // Weekly count
        $weeklyResult = DB::table('attendance_records')
            ->whereBetween('created_at', [$weekStart, $end])
            ->whereIn('status', ['Present', 'Late', 'Excused'])
            ->whereNotNull('location')
            ->whereRaw("(
                6371 * acos(
                    cos(radians(?)) * cos(radians(
                        CASE 
                            WHEN location LIKE '{\"lat\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lat\":', -1), ',', 1)
                            WHEN location LIKE '%\"latitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"latitude\":', -1), ',', 1)
                            ELSE SUBSTRING_INDEX(location, ',', 1)
                        END
                    )) * sin(radians(
                        CASE 
                            WHEN location LIKE '{\"lng\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lng\":', -1), '}', 1)
                            WHEN location LIKE '%\"longitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"longitude\":', -1), ',', 1)
                            ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(location, ',', -1), '}', 1)
                        END
                    )) - cos(radians(?)) * cos(radians(
                        CASE 
                            WHEN location LIKE '{\"lat\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lat\":', -1), ',', 1)
                            WHEN location LIKE '%\"latitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"latitude\":', -1), ',', 1)
                            ELSE SUBSTRING_INDEX(location, ',', 1)
                        END
                    )) * sin(radians(
                        CASE 
                            WHEN location LIKE '{\"lng\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lng\":', -1), '}', 1)
                            WHEN location LIKE '%\"longitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"longitude\":', -1), ',', 1)
                            ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(location, ',', -1), '}', 1)
                        END
                    ))
                )
            ) > ?", [$pncLat, $pncLng, $radiusKm])
            ->count();

        // Today's count
        $todayEnd = $todayStart->copy()->endOfDay();
        $todayResult = DB::table('attendance_records')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->whereIn('status', ['Present', 'Late', 'Excused'])
            ->whereNotNull('location')
            ->whereRaw("(
                6371 * acos(
                    cos(radians(?)) * cos(radians(
                        CASE 
                            WHEN location LIKE '{\"lat\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lat\":', -1), ',', 1)
                            WHEN location LIKE '%\"latitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"latitude\":', -1), ',', 1)
                            ELSE SUBSTRING_INDEX(location, ',', 1)
                        END
                    )) * sin(radians(
                        CASE 
                            WHEN location LIKE '{\"lng\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lng\":', -1), '}', 1)
                            WHEN location LIKE '%\"longitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"longitude\":', -1), ',', 1)
                            ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(location, ',', -1), '}', 1)
                        END
                    )) - cos(radians(?)) * cos(radians(
                        CASE 
                            WHEN location LIKE '{\"lat\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lat\":', -1), ',', 1)
                            WHEN location LIKE '%\"latitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"latitude\":', -1), ',', 1)
                            ELSE SUBSTRING_INDEX(location, ',', 1)
                        END
                    )) * sin(radians(
                        CASE 
                            WHEN location LIKE '{\"lng\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"lng\":', -1), '}', 1)
                            WHEN location LIKE '%\"longitude\":%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(location, '\"longitude\":', -1), ',', 1)
                            ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(location, ',', -1), '}', 1)
                        END
                    ))
                )
            ) > ?", [$pncLat, $pncLng, $radiusKm])
            ->count();

        return [
            'today' => $todayResult,
            'weekly' => $weeklyResult,
            'monthly' => $monthlyResult,
        ];
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

        if (
            preg_match('/lat(?:itude)?\s*[:=]\s*(-?\d+(?:\.\d+)?)/i', $location, $latMatch) &&
            preg_match('/lng|lon|longitude\s*[:=]\s*(-?\d+(?:\.\d+)?)/i', $location, $lngMatch)
        ) {
            return [(float) $latMatch[1], (float) $lngMatch[1]];
        }

        return null;
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}

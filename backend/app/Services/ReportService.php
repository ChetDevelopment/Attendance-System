<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Cache TTL in seconds (5 minutes)
     */
    const CACHE_TTL = 300;
    private const PRESENT_STATUSES = ['present', 'Present', 'late', 'Late'];
    private const ABSENT_STATUSES = ['absent', 'Absent'];
    private const LATE_STATUSES = ['late', 'Late'];

    /**
     * Get comprehensive student attendance report
     * OPTIMIZED: Single query with conditional aggregation
     */
    public function getStudentReport($studentId): array
    {
        $cacheKey = "report:student:{$studentId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($studentId) {
            $student = Student::with('class')->findOrFail($studentId);
            
            // OPTIMIZATION: Single query with conditional aggregation
            $stats = AttendanceRecord::where('student_id', $studentId)
                ->selectRaw(
                    "COUNT(*) as total,
                     SUM(CASE WHEN status IN ('present', 'late', 'Present', 'Late') THEN 1 ELSE 0 END) as present_count,
                     SUM(CASE WHEN status IN ('absent', 'Absent') THEN 1 ELSE 0 END) as absent_count,
                     SUM(CASE WHEN status IN ('late', 'Late') THEN 1 ELSE 0 END) as late_count"
                )
                ->first();

            $totalRecords = (int) $stats->total;
            $presentRecords = (int) $stats->present_count;
            $absentRecords = (int) $stats->absent_count;
            $lateRecords = (int) $stats->late_count;

            $attendancePercentage = $totalRecords > 0 
                ? round(($presentRecords / $totalRecords) * 100, 2) 
                : 0;

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->fullname,
                    'username' => $student->username,
                    'class' => $student->class?->class_name,
                ],
                'summary' => [
                    'total_sessions' => $totalRecords,
                    'present' => $presentRecords,
                    'absent' => $absentRecords,
                    'late' => $lateRecords,
                    'attendance_percentage' => $attendancePercentage,
                ],
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get student attendance report by month
     */
    public function getStudentReportByMonth($studentId, $month, $year): array
    {
        $cacheKey = "report:student:{$studentId}:month:{$year}-{$month}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($studentId, $month, $year) {
            $student = Student::with('class')->findOrFail($studentId);
            
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $records = AttendanceRecord::where('student_id', $studentId)
                ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->with('session')
                ->orderBy('attendance_date', 'desc')
                ->get();

            $totalRecords = $records->count();
            $presentRecords = $records->whereIn('status', self::PRESENT_STATUSES)->count();
            $absentRecords = $records->whereIn('status', self::ABSENT_STATUSES)->count();
            $lateRecords = $records->whereIn('status', self::LATE_STATUSES)->count();

            $attendancePercentage = $totalRecords > 0 
                ? round(($presentRecords / $totalRecords) * 100, 2) 
                : 0;

            // Group by date for daily breakdown
            $dailyBreakdown = $records->groupBy(function($item) {
                return $item->attendance_date->format('Y-m-d');
            })->map(function ($dayRecords, $date) {
                return [
                    'date' => $date,
                    'sessions' => $dayRecords->map(function ($record) {
                        return [
                            'session_id' => $record->session_id,
                            'session_name' => $record->session?->name,
                            'status' => $record->status,
                            'check_in_time' => $record->check_in_time ? $record->check_in_time->toIso8601String() : null,
                        ];
                    })->values(),
                ];
            })->values();

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->fullname,
                    'class' => $student->class?->class_name,
                ],
                'period' => [
                    'month' => $month,
                    'year' => $year,
                    'month_name' => Carbon::createFromDate($year, $month)->format('F'),
                ],
                'summary' => [
                    'total_sessions' => $totalRecords,
                    'present' => $presentRecords,
                    'absent' => $absentRecords,
                    'late' => $lateRecords,
                    'attendance_percentage' => $attendancePercentage,
                ],
                'daily_breakdown' => $dailyBreakdown,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get student attendance report by year
     */
    public function getStudentReportByYear($studentId, $year): array
    {
        $cacheKey = "report:student:{$studentId}:year:{$year}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($studentId, $year) {
            $student = Student::with('class')->findOrFail($studentId);
            
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();

            $records = AttendanceRecord::where('student_id', $studentId)
                ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();

            $totalRecords = $records->count();
            $presentRecords = $records->whereIn('status', self::PRESENT_STATUSES)->count();
            $absentRecords = $records->whereIn('status', self::ABSENT_STATUSES)->count();
            $lateRecords = $records->whereIn('status', self::LATE_STATUSES)->count();

            $attendancePercentage = $totalRecords > 0 
                ? round(($presentRecords / $totalRecords) * 100, 2) 
                : 0;

            // Monthly breakdown
            $monthlyBreakdown = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthRecords = $records->filter(function ($record) use ($year, $month) {
                    $date = Carbon::parse($record->attendance_date);
                    return $date->year == $year && $date->month == $month;
                });

                $monthTotal = $monthRecords->count();
                $monthPresent = $monthRecords->whereIn('status', self::PRESENT_STATUSES)->count();
                $monthAbsent = $monthRecords->whereIn('status', self::ABSENT_STATUSES)->count();
                $monthLate = $monthRecords->whereIn('status', self::LATE_STATUSES)->count();

                $monthlyBreakdown[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($year, $month)->format('F'),
                    'total_sessions' => $monthTotal,
                    'present' => $monthPresent,
                    'absent' => $monthAbsent,
                    'late' => $monthLate,
                    'attendance_percentage' => $monthTotal > 0 ? round(($monthPresent / $monthTotal) * 100, 2) : 0,
                ];
            }

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->fullname,
                    'class' => $student->class?->class_name,
                ],
                'period' => [
                    'year' => $year,
                ],
                'summary' => [
                    'total_sessions' => $totalRecords,
                    'present' => $presentRecords,
                    'absent' => $absentRecords,
                    'late' => $lateRecords,
                    'attendance_percentage' => $attendancePercentage,
                ],
                'monthly_breakdown' => $monthlyBreakdown,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get class attendance report
     * OPTIMIZED: Single query with conditional aggregation
     */
    public function getClassReport($classId): array
    {
        $cacheKey = "report:class:{$classId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($classId) {
            $class = StudentClass::findOrFail($classId);
            
            $students = Student::where('class_id', $classId)->get();
            $studentIds = $students->pluck('id');

            // OPTIMIZATION: Single query with conditional aggregation
            $stats = AttendanceRecord::whereIn('student_id', $studentIds)
                ->selectRaw(
                    "COUNT(*) as total,
                     SUM(CASE WHEN status IN ('present', 'late', 'Present', 'Late') THEN 1 ELSE 0 END) as present_count,
                     SUM(CASE WHEN status IN ('absent', 'Absent') THEN 1 ELSE 0 END) as absent_count,
                     SUM(CASE WHEN status IN ('late', 'Late') THEN 1 ELSE 0 END) as late_count"
                )
                ->first();

            $totalRecords = (int) $stats->total;
            $presentRecords = (int) $stats->present_count;
            $absentRecords = (int) $stats->absent_count;
            $lateRecords = (int) $stats->late_count;

            $attendancePercentage = $totalRecords > 0 
                ? round(($presentRecords / $totalRecords) * 100, 2) 
                : 0;

            // Get recent sessions with attendance
            $recentSessions = AttendanceRecord::whereIn('student_id', $studentIds)
                ->with('session')
                ->select('session_id', DB::raw('COUNT(*) as total'), 
                    DB::raw('SUM(CASE WHEN status IN ("present", "late", "Present", "Late") THEN 1 ELSE 0 END) as present'),
                    DB::raw('SUM(CASE WHEN status IN ("absent", "Absent") THEN 1 ELSE 0 END) as absent'))
                ->groupBy('session_id')
                ->limit(10)
                ->get();

            return [
                'class' => [
                    'id' => $class->id,
                    'name' => $class->class_name,
                    'room' => $class->room_number,
                    'total_students' => $students->count(),
                ],
                'summary' => [
                    'total_attendance_records' => $totalRecords,
                    'present' => $presentRecords,
                    'absent' => $absentRecords,
                    'late' => $lateRecords,
                    'attendance_percentage' => $attendancePercentage,
                ],
                'recent_sessions' => $recentSessions,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get class attendance by date range
     */
    public function getClassReportByDateRange($classId, $startDate, $endDate): array
    {
        $cacheKey = "report:class:{$classId}:range:" . md5($startDate . $endDate);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($classId, $startDate, $endDate) {
            $class = StudentClass::findOrFail($classId);
            
            $students = Student::where('class_id', $classId)->get();
            $studentIds = $students->pluck('id');

            // OPTIMIZATION: Single query with conditional aggregation
            $stats = AttendanceRecord::whereIn('student_id', $studentIds)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->selectRaw(
                    "COUNT(*) as total,
                     SUM(CASE WHEN status IN ('present', 'late', 'Present', 'Late') THEN 1 ELSE 0 END) as present_count,
                     SUM(CASE WHEN status IN ('absent', 'Absent') THEN 1 ELSE 0 END) as absent_count,
                     SUM(CASE WHEN status IN ('late', 'Late') THEN 1 ELSE 0 END) as late_count"
                )
                ->first();

            $totalRecords = (int) $stats->total;

            // Get daily breakdown
            $records = AttendanceRecord::whereIn('student_id', $studentIds)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->select('attendance_date', 'status')
                ->orderBy('attendance_date')
                ->get();

            $dailyBreakdown = $records->groupBy(function($item) {
                return Carbon::parse($item->attendance_date)->format('Y-m-d');
            })->map(function ($dayRecords, $date) {
                $total = $dayRecords->count();
                $present = $dayRecords->whereIn('status', self::PRESENT_STATUSES)->count();
                return [
                    'date' => $date,
                    'total' => $total,
                    'present' => $present,
                    'absent' => $dayRecords->whereIn('status', self::ABSENT_STATUSES)->count(),
                    'late' => $dayRecords->whereIn('status', self::LATE_STATUSES)->count(),
                    'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
                ];
            })->values();

            return [
                'class' => [
                    'id' => $class->id,
                    'name' => $class->class_name,
                    'total_students' => $students->count(),
                ],
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'summary' => [
                    'total_attendance_records' => $totalRecords,
                    'present' => (int)$stats->present_count,
                    'absent' => (int)$stats->absent_count,
                    'late' => (int)$stats->late_count,
                    'attendance_percentage' => $totalRecords > 0 ? round(((int)$stats->present_count / $totalRecords) * 100, 2) : 0,
                ],
                'daily_breakdown' => $dailyBreakdown,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get monthly summary for class
     */
    public function getClassMonthlySummary($classId, $month, $year): array
    {
        $cacheKey = "report:class:{$classId}:month:{$year}-{$month}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($classId, $month, $year) {
            $class = StudentClass::findOrFail($classId);
            
            $students = Student::where('class_id', $classId)->get();
            $studentIds = $students->pluck('id');

            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $records = AttendanceRecord::whereIn('student_id', $studentIds)
                ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();

            $totalRecords = $records->count();
            $presentRecords = $records->whereIn('status', self::PRESENT_STATUSES)->count();
            $absentRecords = $records->whereIn('status', self::ABSENT_STATUSES)->count();
            $lateRecords = $records->whereIn('status', self::LATE_STATUSES)->count();

            $sessionNames = Session::whereIn('id', $records->pluck('session_id')->unique()->values())
                ->pluck('name', 'id');

            $sessionSummary = $records->groupBy('session_id')->map(function ($sessionRecords, $sessionId) use ($sessionNames) {
                return [
                    'session_id' => $sessionId,
                    'session_name' => $sessionNames->get($sessionId, 'Unknown'),
                    'total' => $sessionRecords->count(),
                    'present' => $sessionRecords->whereIn('status', self::PRESENT_STATUSES)->count(),
                    'absent' => $sessionRecords->whereIn('status', self::ABSENT_STATUSES)->count(),
                    'late' => $sessionRecords->whereIn('status', self::LATE_STATUSES)->count(),
                ];
            })->values();

            return [
                'class' => [
                    'id' => $class->id,
                    'name' => $class->class_name,
                ],
                'period' => [
                    'month' => $month,
                    'year' => $year,
                    'month_name' => Carbon::createFromDate($year, $month)->format('F'),
                ],
                'summary' => [
                    'total_attendance_records' => $totalRecords,
                    'present' => $presentRecords,
                    'absent' => $absentRecords,
                    'late' => $lateRecords,
                    'attendance_percentage' => $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100, 2) : 0,
                ],
                'session_summary' => $sessionSummary,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get attendance records by date range
     */
    public function getAttendanceByDateRange($startDate, $endDate, $classId = null, $studentId = null): array
    {
        $query = AttendanceRecord::with(['student:id,fullname', 'session:id,name']);

        if ($startDate && $endDate) {
            $query->whereBetween('attendance_date', [$startDate, $endDate]);
        }

        if ($classId) {
            $studentIds = Student::where('class_id', $classId)->pluck('id');
            $query->whereIn('student_id', $studentIds);
        }

        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        $records = $query->orderBy('attendance_date', 'desc')->paginate(50);

        return [
            'records' => collect($records->items())->map(function ($record) {
                return [
                    'id' => $record->id,
                    'student' => [
                        'id' => $record->student_id,
                        'name' => $record->student?->fullname,
                    ],
                    'session' => [
                        'id' => $record->session_id,
                        'name' => $record->session?->name,
                    ],
                    'status' => $record->status,
                    'date' => $record->attendance_date->format('Y-m-d'),
                    'created_at' => $record->created_at->toIso8601String(),
                ];
            }),
            'pagination' => [
                'total' => $records->total(),
                'per_page' => $records->perPage(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Clear cache for a specific student or class
     */
    public function clearCache($type, $id): void
    {
        if ($type === 'student') {
            Cache::forget("report:student:{$id}");
        } elseif ($type === 'class') {
            Cache::forget("report:class:{$id}");
        }
    }
}

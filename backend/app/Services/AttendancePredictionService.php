<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Student;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AttendancePredictionService
{
    /**
     * Cache TTL in seconds (10 minutes)
     */
    const CACHE_TTL = 600;

    /**
     * Risk score thresholds
     */
    const RISK_LOW = 25;
    const RISK_MEDIUM = 50;
    const RISK_HIGH = 75;
    private const PRESENT_STATUSES = ['present', 'Present', 'late', 'Late'];
    private const ABSENT_STATUSES = ['absent', 'Absent'];

    /**
     * Analyze historical attendance data
     */
    public function analyzeHistoricalData($days = 30): array
    {
        $cacheKey = "prediction:historical:{$days}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($days) {
            $startDate = Carbon::now()->subDays($days)->toDateString();
            $endDate = Carbon::now()->toDateString();

            $summary = $this->aggregateAttendanceStats($startDate, $endDate);

            // Day of week patterns
            $dayOfWeekPatterns = $this->getDayOfWeekPatterns($startDate, $endDate);

            // Recent trends (last 7 days vs previous 7 days)
            $recentTrend = $this->calculateRecentTrend();

            // Session patterns
            $sessionPatterns = $this->getSessionPatterns($startDate, $endDate);

            return [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'days' => $days,
                ],
                'summary' => [
                    'total_records' => $summary['total'],
                    'present' => $summary['present'],
                    'absent' => $summary['absent'],
                    'attendance_rate' => $summary['total'] > 0
                        ? round(($summary['present'] / $summary['total']) * 100, 2)
                        : 0,
                ],
                'day_of_week_patterns' => $dayOfWeekPatterns,
                'recent_trend' => $recentTrend,
                'session_patterns' => $sessionPatterns,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get day of week attendance patterns
     */
    private function getDayOfWeekPatterns($startDate, $endDate): array
    {
        $records = AttendanceRecord::whereBetween('attendance_date', [$startDate, $endDate])
            ->select(DB::raw('DAYOFWEEK(attendance_date) as day'), 
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("absent", "Absent") THEN 1 ELSE 0 END) as absent'))
            ->groupBy(DB::raw('DAYOFWEEK(attendance_date)'))
            ->get();

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $patterns = [];

        foreach ($records as $record) {
            $dayIndex = (int)$record->day - 1; // MySQL DAYOFWEEK starts at 1
            $patterns[] = [
                'day' => $dayNames[$dayIndex] ?? 'Unknown',
                'day_index' => $dayIndex,
                'total' => $record->total,
                'absent' => $record->absent,
                'absence_rate' => $record->total > 0 
                    ? round(($record->absent / $record->total) * 100, 2) 
                    : 0,
            ];
        }

        return $patterns;
    }

    /**
     * Calculate recent attendance trend
     */
    private function calculateRecentTrend(): array
    {
        $now = Carbon::now();
        
        // Last 7 days
        $last7Start = $now->copy()->subDays(7)->toDateString();
        $last7End = $now->toDateString();
        
        // Previous 7 days
        $prev7Start = $now->copy()->subDays(14)->toDateString();
        $prev7End = $now->copy()->subDays(8)->toDateString();

        $last7 = $this->aggregateAttendanceStats($last7Start, $last7End);
        $prev7 = $this->aggregateAttendanceStats($prev7Start, $prev7End);
        $last7Rate = $last7['total'] > 0 ? ($last7['present'] / $last7['total']) * 100 : 0;
        $prev7Rate = $prev7['total'] > 0 ? ($prev7['present'] / $prev7['total']) * 100 : 0;

        $trendDiff = $last7Rate - $prev7Rate;
        $trend = 'stable';
        if ($trendDiff > 5) $trend = 'improving';
        elseif ($trendDiff < -5) $trend = 'declining';

        return [
            'last_7_days_rate' => round($last7Rate, 2),
            'previous_7_days_rate' => round($prev7Rate, 2),
            'change' => round($trendDiff, 2),
            'trend' => $trend,
        ];
    }

    /**
     * Get session-based patterns
     */
    private function getSessionPatterns($startDate, $endDate): array
    {
        $records = AttendanceRecord::whereBetween('attendance_date', [$startDate, $endDate])
            ->join('sessions', 'attendance_records.session_id', '=', 'sessions.id')
            ->select('sessions.id', 
                'sessions.name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN attendance_records.status IN ("absent", "Absent") THEN 1 ELSE 0 END) as absent'))
            ->groupBy('sessions.id', 'sessions.name')
            ->get();

        return $records->map(function ($record) {
            return [
                'session_id' => $record->id,
                'session_name' => $record->name,
                'total' => $record->total,
                'absent' => $record->absent,
                'absence_rate' => $record->total > 0 
                    ? round(($record->absent / $record->total) * 100, 2) 
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Predict individual student absence
     */
    public function predictStudentAbsence($studentId): array
    {
        $cacheKey = "prediction:student:{$studentId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($studentId) {
            $student = Student::with('class')->findOrFail($studentId);
            $attendanceStats = $this->getStudentAttendanceStats($studentId, 30);
            $riskScore = $this->calculateRiskScoreFromData($attendanceStats, $this->getConsecutiveAbsences($studentId));
            $consecutiveAbsences = $this->getConsecutiveAbsences($studentId);
            $dayPattern = $this->getStudentDayPattern($studentId);
            $prediction = $this->determinePrediction($riskScore, $attendanceStats, $consecutiveAbsences, $dayPattern);
            $factors = $this->buildRiskFactors($attendanceStats, $consecutiveAbsences, $dayPattern);

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->fullname,
                    'class' => $student->class?->class_name,
                ],
                'attendance_rate' => $attendanceStats['rate'],
                'total_sessions' => $attendanceStats['total'],
                'absent_sessions' => $attendanceStats['absent'],
                'trend' => $attendanceStats['trend'],
                'risk_score' => $riskScore,
                'risk_level' => $this->getRiskLevel($riskScore),
                'prediction' => $prediction['prediction'],
                'confidence' => $prediction['confidence'],
                'consecutive_absences' => $consecutiveAbsences,
                'day_pattern' => $dayPattern,
                'factors' => $factors,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    private function getStudentAttendanceStats($studentId, $days): array
    {
        $startDate = Carbon::now()->subDays($days)->toDateString();
        $endDate = Carbon::now()->toDateString();
        $summary = $this->aggregateAttendanceStats($startDate, $endDate, $studentId);
        $trend = $this->getStudentTrend($studentId);

        return [
            'total' => $summary['total'],
            'present' => $summary['present'],
            'absent' => $summary['absent'],
            'rate' => $summary['total'] > 0 ? round(($summary['present'] / $summary['total']) * 100, 2) : 100,
            'trend' => $trend,
        ];
    }

    private function getStudentTrend($studentId): string
    {
        $now = Carbon::now();
        $last7Start = $now->copy()->subDays(7)->toDateString();
        $last7End = $now->toDateString();
        $prev7Start = $now->copy()->subDays(14)->toDateString();
        $prev7End = $now->copy()->subDays(8)->toDateString();

        $last7 = $this->aggregateAttendanceStats($last7Start, $last7End, $studentId);
        $prev7 = $this->aggregateAttendanceStats($prev7Start, $prev7End, $studentId);
        $last7Rate = $last7['total'] > 0 ? ($last7['present'] / $last7['total']) * 100 : 0;
        $prev7Rate = $prev7['total'] > 0 ? ($prev7['present'] / $prev7['total']) * 100 : 0;

        $trendDiff = $last7Rate - $prev7Rate;
        if ($trendDiff > 10) return 'improving';
        if ($trendDiff < -10) return 'declining';
        return 'stable';
    }

    private function getConsecutiveAbsences($studentId): int
    {
        $records = AttendanceRecord::where('student_id', $studentId)
            ->where('attendance_date', '>=', Carbon::now()->subDays(14)->toDateString())
            ->orderBy('attendance_date', 'desc')
            ->get();

        $consecutive = 0;
        foreach ($records as $record) {
            if (in_array($record->status, self::ABSENT_STATUSES, true)) {
                $consecutive++;
            } else {
                break;
            }
        }
        return $consecutive;
    }

    private function getStudentDayPattern($studentId): ?array
    {
        $records = AttendanceRecord::where('student_id', $studentId)
            ->where('attendance_date', '>=', Carbon::now()->subDays(60)->toDateString())
            ->select(DB::raw('DAYOFWEEK(attendance_date) as day'), 
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("absent", "Absent") THEN 1 ELSE 0 END) as absent'))
            ->groupBy(DB::raw('DAYOFWEEK(attendance_date)'))
            ->get();

        if ($records->isEmpty()) return null;

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $highestRiskDay = null;
        $highestAbsenceRate = 0;

        foreach ($records as $record) {
            $dayIndex = (int)$record->day - 1;
            $absenceRate = $record->total > 0 ? ($record->absent / $record->total) * 100 : 0;
            if ($absenceRate > $highestAbsenceRate) {
                $highestAbsenceRate = $absenceRate;
                $highestRiskDay = $dayNames[$dayIndex] ?? 'Unknown';
            }
        }

        return [
            'highest_risk_day' => $highestAbsenceRate > 20 ? $highestRiskDay : null,
            'absence_rate' => round($highestAbsenceRate, 2),
        ];
    }

    private function determinePrediction($riskScore, $attendanceStats, $consecutiveAbsences, $dayPattern): array
    {
        $prediction = 'uncertain';
        $confidence = 50;
        $today = Carbon::now()->dayOfWeek;
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $todayName = $dayNames[$today] ?? 'Unknown';
        $highRiskDay = $dayPattern['highest_risk_day'] ?? null;

        if ($riskScore >= 75) {
            $prediction = 'likely_absent';
            $confidence = 85;
        } elseif ($riskScore >= 50) {
            if ($highRiskDay === $todayName && ($dayPattern['absence_rate'] ?? 0) > 30) {
                $prediction = 'likely_absent';
                $confidence = 70;
            } else {
                $prediction = 'uncertain';
                $confidence = 60;
            }
        } elseif ($riskScore >= 25) {
            $prediction = 'likely_present';
            $confidence = 70;
        } else {
            $prediction = 'likely_present';
            $confidence = 90;
        }

        if ($consecutiveAbsences >= 3) {
            $prediction = 'likely_absent';
            $confidence = 95;
        }

        return ['prediction' => $prediction, 'confidence' => $confidence];
    }

    private function buildRiskFactors($attendanceStats, $consecutiveAbsences, $dayPattern): array
    {
        $factors = [];
        if ($attendanceStats['rate'] < 80) {
            $factors[] = ['factor' => 'Low attendance rate', 'impact' => 'high', 'description' => "Attendance rate is {$attendanceStats['rate']}%"];
        }
        if ($attendanceStats['trend'] === 'declining') {
            $factors[] = ['factor' => 'Declining attendance trend', 'impact' => 'high', 'description' => 'Attendance has been declining recently'];
        }
        if ($consecutiveAbsences >= 2) {
            $factors[] = ['factor' => 'Consecutive absences', 'impact' => 'high', 'description' => "Student has {$consecutiveAbsences} consecutive absences"];
        }
        if ($dayPattern['highest_risk_day'] ?? null) {
            $factors[] = ['factor' => 'Day pattern', 'impact' => 'medium', 'description' => "Tends to be absent on {$dayPattern['highest_risk_day']}s"];
        }
        return $factors;
    }

    private function getRiskLevel($score): string
    {
        if ($score >= self::RISK_HIGH) return 'CRITICAL';
        if ($score >= self::RISK_MEDIUM) return 'HIGH';
        if ($score >= self::RISK_LOW) return 'MEDIUM';
        return 'LOW';
    }

    public function getAtRiskStudents($threshold = 30): array
    {
        $cacheKey = "prediction:at_risk:{$threshold}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($threshold) {
            $students = Student::with('class')->get();
            $atRiskStudents = [];
            $now = Carbon::now();
            $last30Start = $now->copy()->subDays(30)->toDateString();
            $last14Start = $now->copy()->subDays(14)->toDateString();
            $last7Start = $now->copy()->subDays(7)->toDateString();
            $last7End = $now->toDateString();
            $prev7Start = $now->copy()->subDays(14)->toDateString();
            $prev7End = $now->copy()->subDays(8)->toDateString();
            $last60Start = $now->copy()->subDays(60)->toDateString();

            $statsByStudent = AttendanceRecord::where('attendance_date', '>=', $last30Start)
                ->selectRaw("student_id, COUNT(*) as total, SUM(CASE WHEN status IN ('present', 'Present', 'late', 'Late') THEN 1 ELSE 0 END) as present, SUM(CASE WHEN status IN ('absent', 'Absent') THEN 1 ELSE 0 END) as absent")
                ->groupBy('student_id')->get()->keyBy('student_id');

            $trendByStudent = AttendanceRecord::where('attendance_date', '>=', $prev7Start)
                ->where('attendance_date', '<=', $last7End)
                ->selectRaw("student_id, SUM(CASE WHEN attendance_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as last7_total, SUM(CASE WHEN attendance_date BETWEEN ? AND ? AND status IN ('present', 'Present', 'late', 'Late') THEN 1 ELSE 0 END) as last7_present, SUM(CASE WHEN attendance_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as prev7_total, SUM(CASE WHEN attendance_date BETWEEN ? AND ? AND status IN ('present', 'Present', 'late', 'Late') THEN 1 ELSE 0 END) as prev7_present", [$last7Start, $last7End, $last7Start, $last7End, $prev7Start, $prev7End, $prev7Start, $prev7End])
                ->groupBy('student_id')->get()->keyBy('student_id');

            $recentRecords = AttendanceRecord::where('attendance_date', '>=', $last14Start)
                ->select('student_id', 'status', 'attendance_date')->orderBy('student_id')->orderByDesc('attendance_date')->get()->groupBy('student_id');

            $consecutiveByStudent = [];
            foreach ($recentRecords as $sid => $recs) {
                $c = 0;
                foreach ($recs as $r) {
                    if (in_array($r->status, self::ABSENT_STATUSES, true)) { $c++; continue; }
                    break;
                }
                $consecutiveByStudent[$sid] = $c;
            }

            foreach ($students as $student) {
                $sRow = $statsByStudent->get($student->id);
                $tRow = trendByStudent->get($student->id) ?? (object)['last7_total'=>0,'last7_present'=>0,'prev7_total'=>0,'prev7_present'=>0];
                $total = (int)($sRow->total ?? 0);
                $present = (int)($sRow->present ?? 0);
                
                $last7Rate = $tRow->last7_total > 0 ? ($tRow->last7_present / $tRow->last7_total) * 100 : 0;
                $prev7Rate = $tRow->prev7_total > 0 ? ($tRow->prev7_present / $tRow->prev7_total) * 100 : 0;
                $trendDiff = $last7Rate - $prev7Rate;
                $trend = $trendDiff > 10 ? 'improving' : ($trendDiff < -10 ? 'declining' : 'stable');
                
                $stats = ['total' => $total, 'present' => $present, 'absent' => (int)($sRow->absent ?? 0), 'rate' => $total > 0 ? round(($present / $total) * 100, 2) : 100, 'trend' => $trend];
                $consecutive = $consecutiveByStudent[$student->id] ?? 0;
                $riskScore = $this->calculateRiskScoreFromData($stats, $consecutive);
                
                if ($riskScore >= (100 - $threshold)) {
                    $atRiskStudents[] = [
                        'student' => ['id' => $student->id, 'name' => $student->fullname, 'class' => $student->class?->class_name],
                        'attendance_rate' => $stats['rate'], 'risk_score' => $riskScore, 'risk_level' => $this->getRiskLevel($riskScore),
                        'trend' => $stats['trend'], 'absent_sessions' => $stats['absent'], 'total_sessions' => $stats['total'], 'consecutive_absences' => $consecutive
                    ];
                }
            }
            usort($atRiskStudents, fn($a, $b) => $b['risk_score'] - $a['risk_score']);
            return ['threshold' => $threshold, 'total_at_risk' => count($atRiskStudents), 'students' => $atRiskStudents, 'generated_at' => now()->toIso8601String()];
        });
    }

    public function generateInsights(): array
    {
        return Cache::remember("prediction:insights", self::CACHE_TTL, function () {
            $historical = $this->analyzeHistoricalData(30);
            $atRisk = $this->getAtRiskStudents(30);
            $weekly = $this->getWeeklyPrediction(0);
            return [
                'summary' => ['overall_attendance_rate' => $historical['summary']['attendance_rate'], 'at_risk_students_count' => $atRisk['total_at_risk'], 'trend' => $historical['recent_trend']['trend']],
                'historical' => ['attendance_rate' => $historical['summary']['attendance_rate'], 'trend' => $historical['recent_trend']],
                'predictions' => ['expected_absences_next_week' => $weekly['predicted_absences'], 'highest_risk_days' => $weekly['highest_risk_days']],
                'at_risk' => ['count' => $atRisk['total_at_risk'], 'critical_count' => count(array_filter($atRisk['students'], fn($s) => $s['risk_level'] === 'CRITICAL')), 'high_count' => count(array_filter($atRisk['students'], fn($s) => $s['risk_level'] === 'HIGH'))],
                'recommendations' => $this->generateRecommendations($historical, $atRisk, $weekly),
                'generated_at' => now()->toIso8601String()
            ];
        });
    }

    private function generateRecommendations($historical, $atRisk, $weekly): array
    {
        $recommendations = [];
        if ($historical['recent_trend']['trend'] === 'declining') $recommendations[] = ['priority' => 'high', 'category' => 'trend', 'message' => 'Overall attendance is declining.', 'action' => 'Review recent policies.'];
        if ($atRisk['total_at_risk'] > 0) {
            $criticalCount = count(array_filter($atRisk['students'], fn($s) => $s['risk_level'] === 'CRITICAL'));
            if ($criticalCount > 0) $recommendations[] = ['priority' => 'critical', 'category' => 'intervention', 'message' => "{$criticalCount} students at critical risk level.", 'action' => 'Contact parents immediately.'];
        }
        return $recommendations ?: [['priority' => 'low', 'category' => 'maintenance', 'message' => 'Attendance is stable.', 'action' => 'Continue monitoring.']];
    }

    public function getWeeklyPrediction($weekOffset = 0): array
    {
        return Cache::remember("prediction:weekly:{$weekOffset}", self::CACHE_TTL, function () use ($weekOffset) {
            $startDate = Carbon::now()->addWeeks($weekOffset)->startOfWeek();
            $historical = $this->analyzeHistoricalData(60);
            $totalStudents = Student::count();
            $dailyPredictions = [];
            $totalPredictedAbsences = 0;
            $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            foreach ($dayNames as $index => $dayName) {
                $dayPattern = collect($historical['day_of_week_patterns'])->firstWhere('day', $dayName);
                $expectedAbsences = $dayPattern ? round(($dayPattern['absence_rate'] / 100) * $totalStudents) : 0;
                $dailyPredictions[] = ['day' => $dayName, 'date' => $startDate->copy()->addDays($index)->toDateString(), 'expected_absences' => $expectedAbsences, 'historical_absence_rate' => $dayPattern['absence_rate'] ?? 0];
                $totalPredictedAbsences += $expectedAbsences;
            }
            $highestRiskDays = collect($dailyPredictions)->sortByDesc('expected_absences')->take(3)->pluck('day')->toArray();
            return ['week' => ['start_date' => $startDate->toDateString(), 'end_date' => $startDate->copy()->endOfWeek()->toDateString(), 'week_offset' => $weekOffset], 'predicted_absences' => $totalPredictedAbsences, 'daily_predictions' => $dailyPredictions, 'highest_risk_days' => $highestRiskDays, 'generated_at' => now()->toIso8601String()];
        });
    }

    public function clearCache(): void
    {
        Cache::forget('prediction:historical:30');
        Cache::forget('prediction:historical:60');
        Cache::forget('prediction:insights');
        Cache::forget('prediction:weekly:0');
        Cache::forget("prediction:at_risk:30");
        Student::query()->select('id')->chunkById(500, function ($students): void {
            foreach ($students as $student) { Cache::forget("prediction:student:{$student->id}"); }
        });
    }

    private function aggregateAttendanceStats(string $startDate, string $endDate, ?int $studentId = null): array
    {
        $query = AttendanceRecord::whereBetween('attendance_date', [$startDate, $endDate])
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status IN ('present', 'Present', 'late', 'Late') THEN 1 ELSE 0 END) as present, SUM(CASE WHEN status IN ('absent', 'Absent') THEN 1 ELSE 0 END) as absent");
        if ($studentId) $query->where('student_id', $studentId);
        $row = $query->first();
        return ['total' => (int)($row->total ?? 0), 'present' => (int)($row->present ?? 0), 'absent' => (int)($row->absent ?? 0)];
    }

    private function calculateRiskScoreFromData(array $attendanceStats, int $consecutiveAbsences): int
    {
        $score = (100 - $attendanceStats['rate']) * 0.4;
        if ($attendanceStats['trend'] === 'declining') $score += 25;
        elseif ($attendanceStats['trend'] === 'improving') $score -= 10;
        if ($consecutiveAbsences >= 3) $score += 30;
        elseif ($consecutiveAbsences >= 2) $score += 15;
        $absenceRate = $attendanceStats['total'] > 0 ? ($attendanceStats['absent'] / $attendanceStats['total']) * 100 : 0;
        if ($absenceRate > 30) $score += 20;
        elseif ($absenceRate > 20) $score += 10;
        return min(100, max(0, (int)$score));
    }
}

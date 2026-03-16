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
        $records = AttendanceRecord::whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw('DAYOFWEEK(date) as day'), 
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("absent", "Absent") THEN 1 ELSE 0 END) as absent'))
            ->groupBy(DB::raw('DAYOFWEEK(date)'))
            ->get();

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $patterns = [];

        foreach ($records as $record) {
            $dayIndex = (int)$record->day - 1; // MySQL DAYOFWEEK starts at 1
            $patterns[] = [
                'day' => $dayNames[$dayIndex],
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
        
        // Previous 7 days (days 8-14)
        $prev7Start = $now->copy()->subDays(14)->toDateString();
        $prev7End = $now->copy()->subDays(8)->toDateString();

        $last7 = $this->aggregateAttendanceStats($last7Start, $last7End);
        $prev7 = $this->aggregateAttendanceStats($prev7Start, $prev7End);
        $last7Rate = $last7['total'] > 0 ? ($last7['present'] / $last7['total']) * 100 : 0;
        $prev7Rate = $prev7['total'] > 0 ? ($prev7['present'] / $prev7['total']) * 100 : 0;

        // Determine trend
        $trendDiff = $last7Rate - $prev7Rate;
        $trend = 'stable';
        if ($trendDiff > 5) {
            $trend = 'improving';
        } elseif ($trendDiff < -5) {
            $trend = 'declining';
        }

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
        $records = AttendanceRecord::whereBetween('date', [$startDate, $endDate])
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
            
            // Get attendance history (last 30 days)
            $attendanceStats = $this->getStudentAttendanceStats($studentId, 30);
            
            // Calculate risk score
            $riskScore = $this->calculateRiskScore($studentId, $attendanceStats);
            
            // Get recent consecutive absences
            $consecutiveAbsences = $this->getConsecutiveAbsences($studentId);
            
            // Get day of week pattern for this student
            $dayPattern = $this->getStudentDayPattern($studentId);
            
            // Determine prediction
            $prediction = $this->determinePrediction($riskScore, $attendanceStats, $consecutiveAbsences, $dayPattern);
            
            // Build factors array
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

    /**
     * Get student attendance statistics
     */
    private function getStudentAttendanceStats($studentId, $days): array
    {
        $startDate = Carbon::now()->subDays($days)->toDateString();
        $endDate = Carbon::now()->toDateString();

        $summary = $this->aggregateAttendanceStats($startDate, $endDate, $studentId);

        // Calculate trend (last 7 days vs previous 7 days)
        $trend = $this->getStudentTrend($studentId);

        return [
            'total' => $summary['total'],
            'present' => $summary['present'],
            'absent' => $summary['absent'],
            'rate' => $summary['total'] > 0 ? round(($summary['present'] / $summary['total']) * 100, 2) : 100,
            'trend' => $trend,
        ];
    }

    /**
     * Get student's attendance trend
     */
    private function getStudentTrend($studentId): string
    {
        $now = Carbon::now();
        
        // Last 7 days
        $last7Start = $now->copy()->subDays(7)->toDateString();
        $last7End = $now->toDateString();
        
        // Previous 7 days
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

    /**
     * Calculate risk score (0-100)
     */
    private function calculateRiskScore($studentId, $attendanceStats): int
    {
        $score = 0;
        
        // Base: Inverted attendance rate (lower attendance = higher risk)
        $baseScore = 100 - $attendanceStats['rate'];
        $score += $baseScore * 0.4; // 40% weight

        // Recent trend: Declining attendance adds risk
        if ($attendanceStats['trend'] === 'declining') {
            $score += 25;
        } elseif ($attendanceStats['trend'] === 'improving') {
            $score -= 10;
        }

        // Consecutive absences
        $consecutive = $this->getConsecutiveAbsences($studentId);
        if ($consecutive >= 3) {
            $score += 30;
        } elseif ($consecutive >= 2) {
            $score += 15;
        }

        // High absence frequency
        $absenceRate = $attendanceStats['total'] > 0 
            ? ($attendanceStats['absent'] / $attendanceStats['total']) * 100 
            : 0;
        
        if ($absenceRate > 30) {
            $score += 20;
        } elseif ($absenceRate > 20) {
            $score += 10;
        }

        // Cap at 0-100
        return min(100, max(0, (int)$score));
    }

    /**
     * Get consecutive absences count
     */
    private function getConsecutiveAbsences($studentId): int
    {
        // Get last 14 days of attendance records ordered by date desc
        $records = AttendanceRecord::where('student_id', $studentId)
            ->where('date', '>=', Carbon::now()->subDays(14)->toDateString())
            ->orderBy('date', 'desc')
            ->pluck('status', 'date')
            ->toArray();

        $consecutive = 0;
        foreach ($records as $date => $status) {
            if (in_array($status, self::ABSENT_STATUSES, true)) {
                $consecutive++;
            } else {
                break;
            }
        }

        return $consecutive;
    }

    /**
     * Get student's day of week pattern
     */
    private function getStudentDayPattern($studentId): ?array
    {
        $records = AttendanceRecord::where('student_id', $studentId)
            ->where('date', '>=', Carbon::now()->subDays(60)->toDateString())
            ->select(DB::raw('DAYOFWEEK(date) as day'), 
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN ("absent", "Absent") THEN 1 ELSE 0 END) as absent'))
            ->groupBy(DB::raw('DAYOFWEEK(date)'))
            ->get();

        if ($records->isEmpty()) {
            return null;
        }

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $highestRiskDay = null;
        $highestAbsenceRate = 0;

        foreach ($records as $record) {
            $dayIndex = (int)$record->day - 1;
            $absenceRate = $record->total > 0 ? ($record->absent / $record->total) * 100 : 0;
            
            if ($absenceRate > $highestAbsenceRate) {
                $highestAbsenceRate = $absenceRate;
                $highestRiskDay = $dayNames[$dayIndex];
            }
        }

        return [
            'highest_risk_day' => $highestAbsenceRate > 20 ? $highestRiskDay : null,
            'absence_rate' => round($highestAbsenceRate, 2),
        ];
    }

    /**
     * Determine prediction
     */
    private function determinePrediction($riskScore, $attendanceStats, $consecutiveAbsences, $dayPattern): array
    {
        $prediction = 'uncertain';
        $confidence = 50;

        // Check today's day pattern
        $today = Carbon::now()->dayOfWeek;
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $todayName = $dayNames[$today];

        $highRiskDay = $dayPattern['highest_risk_day'] ?? null;

        if ($riskScore >= 75) {
            $prediction = 'likely_absent';
            $confidence = 85;
        } elseif ($riskScore >= 50) {
            // Check if today is a high-risk day
            if ($highRiskDay === $todayName && $dayPattern['absence_rate'] > 30) {
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

        // Override if consecutive absences
        if ($consecutiveAbsences >= 3) {
            $prediction = 'likely_absent';
            $confidence = 95;
        }

        return [
            'prediction' => $prediction,
            'confidence' => $confidence,
        ];
    }

    /**
     * Build risk factors array
     */
    private function buildRiskFactors($attendanceStats, $consecutiveAbsences, $dayPattern): array
    {
        $factors = [];

        if ($attendanceStats['rate'] < 80) {
            $factors[] = [
                'factor' => 'Low attendance rate',
                'impact' => 'high',
                'description' => "Attendance rate is {$attendanceStats['rate']}%",
            ];
        }

        if ($attendanceStats['trend'] === 'declining') {
            $factors[] = [
                'factor' => 'Declining attendance trend',
                'impact' => 'high',
                'description' => 'Attendance has been declining in recent days',
            ];
        }

        if ($consecutiveAbsences >= 2) {
            $factors[] = [
                'factor' => 'Consecutive absences',
                'impact' => 'high',
                'description' => "Student has {$consecutiveAbsences} consecutive absences",
            ];
        }

        if (($dayPattern['highest_risk_day'] ?? null)) {
            $factors[] = [
                'factor' => 'Day pattern',
                'impact' => 'medium',
                'description' => "Tends to be absent on {$dayPattern['highest_risk_day']}s",
            ];
        }

        return $factors;
    }

    /**
     * Get risk level from score
     */
    private function getRiskLevel($score): string
    {
        if ($score >= self::RISK_HIGH) return 'CRITICAL';
        if ($score >= self::RISK_MEDIUM) return 'HIGH';
        if ($score >= self::RISK_LOW) return 'MEDIUM';
        return 'LOW';
    }

    /**
     * Get at-risk students
     */
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

            // Aggregate 30-day attendance stats for all students in one query.
            $statsByStudent = AttendanceRecord::where('date', '>=', $last30Start)
                ->selectRaw(
                    "student_id,
                     COUNT(*) as total,
                     SUM(CASE WHEN status IN ('present', 'Present', 'late', 'Late') THEN 1 ELSE 0 END) as present,
                     SUM(CASE WHEN status IN ('absent', 'Absent') THEN 1 ELSE 0 END) as absent"
                )
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id');

            // Aggregate trend windows (last 7 vs previous 7) for all students.
            $trendByStudent = AttendanceRecord::where('date', '>=', $prev7Start)
                ->where('date', '<=', $last7End)
                ->selectRaw(
                    "student_id,
                     SUM(CASE WHEN date BETWEEN ? AND ? THEN 1 ELSE 0 END) as last7_total,
                     SUM(CASE WHEN date BETWEEN ? AND ? AND status IN ('present', 'Present', 'late', 'Late') THEN 1 ELSE 0 END) as last7_present,
                     SUM(CASE WHEN date BETWEEN ? AND ? THEN 1 ELSE 0 END) as prev7_total,
                     SUM(CASE WHEN date BETWEEN ? AND ? AND status IN ('present', 'Present', 'late', 'Late') THEN 1 ELSE 0 END) as prev7_present",
                    [$last7Start, $last7End, $last7Start, $last7End, $prev7Start, $prev7End, $prev7Start, $prev7End]
                )
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id');

            // Pull recent records once for consecutive-absence calculation.
            $recentRecords = AttendanceRecord::where('date', '>=', $last14Start)
                ->select('student_id', 'status', 'date')
                ->orderBy('student_id')
                ->orderByDesc('date')
                ->get()
                ->groupBy('student_id');

            $consecutiveByStudent = [];
            foreach ($recentRecords as $studentId => $records) {
                $consecutive = 0;
                foreach ($records as $record) {
                    if (in_array($record->status, self::ABSENT_STATUSES, true)) {
                        $consecutive++;
                        continue;
                    }
                    break;
                }
                $consecutiveByStudent[$studentId] = $consecutive;
            }

            // Day-of-week pattern summary for all students in one grouped query.
            $dayPatternRows = AttendanceRecord::where('date', '>=', $last60Start)
                ->selectRaw(
                    "student_id,
                     DAYOFWEEK(date) as day,
                     COUNT(*) as total,
                     SUM(CASE WHEN status IN ('absent', 'Absent') THEN 1 ELSE 0 END) as absent"
                )
                ->groupBy('student_id', DB::raw('DAYOFWEEK(date)'))
                ->get()
                ->groupBy('student_id');

            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $dayPatternByStudent = [];
            foreach ($dayPatternRows as $studentId => $rows) {
                $highestRate = 0.0;
                $highestDay = null;
                foreach ($rows as $row) {
                    $rate = (int) $row->total > 0 ? ((int) $row->absent / (int) $row->total) * 100 : 0.0;
                    if ($rate > $highestRate) {
                        $highestRate = $rate;
                        $highestDay = $dayNames[((int) $row->day) - 1] ?? null;
                    }
                }

                $dayPatternByStudent[$studentId] = [
                    'highest_risk_day' => $highestRate > 20 ? $highestDay : null,
                    'absence_rate' => round($highestRate, 2),
                ];
            }

            foreach ($students as $student) {
                $statsRow = $statsByStudent->get($student->id);
                $trendRow = $trendByStudent->get($student->id);
                $total = (int) ($statsRow->total ?? 0);
                $present = (int) ($statsRow->present ?? 0);
                $absent = (int) ($statsRow->absent ?? 0);

                $last7Total = (int) ($trendRow->last7_total ?? 0);
                $last7Present = (int) ($trendRow->last7_present ?? 0);
                $prev7Total = (int) ($trendRow->prev7_total ?? 0);
                $prev7Present = (int) ($trendRow->prev7_present ?? 0);
                $last7Rate = $last7Total > 0 ? ($last7Present / $last7Total) * 100 : 0;
                $prev7Rate = $prev7Total > 0 ? ($prev7Present / $prev7Total) * 100 : 0;
                $trendDiff = $last7Rate - $prev7Rate;
                $trend = $trendDiff > 10 ? 'improving' : ($trendDiff < -10 ? 'declining' : 'stable');
                $consecutive = $consecutiveByStudent[$student->id] ?? 0;
                $dayPattern = $dayPatternByStudent[$student->id] ?? null;

                $stats = [
                    'total' => $total,
                    'present' => $present,
                    'absent' => $absent,
                    'rate' => $total > 0 ? round(($present / $total) * 100, 2) : 100,
                    'trend' => $trend,
                ];

                $riskScore = $this->calculateRiskScoreFromData($stats, $consecutive);
                
                // Check if student meets threshold
                $thresholdScore = 100 - $threshold; // Convert attendance % to risk
                
                if ($riskScore >= $thresholdScore) {
                    $atRiskStudents[] = [
                        'student' => [
                            'id' => $student->id,
                            'name' => $student->fullname,
                            'class' => $student->class?->class_name,
                        ],
                        'attendance_rate' => $stats['rate'],
                        'risk_score' => $riskScore,
                        'risk_level' => $this->getRiskLevel($riskScore),
                        'trend' => $stats['trend'],
                        'absent_sessions' => $stats['absent'],
                        'total_sessions' => $stats['total'],
                        'consecutive_absences' => $consecutive,
                        'day_pattern' => $dayPattern,
                    ];
                }
            }

            // Sort by risk score descending
            usort($atRiskStudents, function ($a, $b) {
                return $b['risk_score'] - $a['risk_score'];
            });

            return [
                'threshold' => $threshold,
                'total_at_risk' => count($atRiskStudents),
                'students' => $atRiskStudents,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Generate overall insights
     */
    public function generateInsights(): array
    {
        $cacheKey = "prediction:insights";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            // Get historical data
            $historical = $this->analyzeHistoricalData(30);
            
            // Get at-risk students
            $atRisk = $this->getAtRiskStudents(30);
            
            // Get weekly prediction
            $weekly = $this->getWeeklyPrediction(0);

            // Generate recommendations
            $recommendations = $this->generateRecommendations($historical, $atRisk, $weekly);

            return [
                'summary' => [
                    'overall_attendance_rate' => $historical['summary']['attendance_rate'],
                    'at_risk_students_count' => $atRisk['total_at_risk'],
                    'trend' => $historical['recent_trend']['trend'],
                ],
                'historical' => [
                    'attendance_rate' => $historical['summary']['attendance_rate'],
                    'trend' => $historical['recent_trend'],
                ],
                'predictions' => [
                    'expected_absences_next_week' => $weekly['predicted_absences'],
                    'highest_risk_days' => $weekly['highest_risk_days'],
                ],
                'at_risk' => [
                    'count' => $atRisk['total_at_risk'],
                    'critical_count' => count(array_filter($atRisk['students'], fn($s) => $s['risk_level'] === 'CRITICAL')),
                    'high_count' => count(array_filter($atRisk['students'], fn($s) => $s['risk_level'] === 'HIGH')),
                ],
                'recommendations' => $recommendations,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Generate recommendations based on data
     */
    private function generateRecommendations($historical, $atRisk, $weekly): array
    {
        $recommendations = [];

        // Check overall trend
        if ($historical['recent_trend']['trend'] === 'declining') {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'trend',
                'message' => 'Overall attendance is declining. Consider investigating root causes.',
                'action' => 'Review recent attendance policies and communicate with students.',
            ];
        }

        // Check at-risk students
        if ($atRisk['total_at_risk'] > 0) {
            $criticalCount = count(array_filter($atRisk['students'], fn($s) => $s['risk_level'] === 'CRITICAL'));
            
            if ($criticalCount > 0) {
                $recommendations[] = [
                    'priority' => 'critical',
                    'category' => 'intervention',
                    'message' => "{$criticalCount} students at critical risk level require immediate intervention.",
                    'action' => 'Contact parents/guardians and arrange counseling sessions.',
                ];
            }
        }

        // Check day patterns
        $highRiskDays = array_filter($historical['day_of_week_patterns'], fn($d) => $d['absence_rate'] > 20);
        if (count($highRiskDays) > 0) {
            $days = implode(', ', array_column($highRiskDays, 'day'));
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'scheduling',
                'message' => "High absence rates detected on: {$days}",
                'action' => 'Review class schedules and consider adjustments.',
            ];
        }

        // Check weekly prediction
        if ($weekly['predicted_absences'] > 10) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'planning',
                'message' => "High number of absences predicted for next week ({$weekly['predicted_absences']} expected).",
                'action' => 'Prepare alternative teaching plans and send reminders to students.',
            ];
        }

        // Default recommendation if everything is fine
        if (empty($recommendations)) {
            $recommendations[] = [
                'priority' => 'low',
                'category' => 'maintenance',
                'message' => 'Attendance is currently stable. Continue monitoring.',
                'action' => 'Maintain current attendance tracking procedures.',
            ];
        }

        return $recommendations;
    }

    /**
     * Get weekly prediction
     */
    public function getWeeklyPrediction($weekOffset = 0): array
    {
        $cacheKey = "prediction:weekly:{$weekOffset}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($weekOffset) {
            $startDate = Carbon::now()->addWeeks($weekOffset)->startOfWeek();
            $endDate = $startDate->copy()->endOfWeek();

            // Get historical data for same week days
            $historical = $this->analyzeHistoricalData(60);
            
            // Calculate expected absences per day
            $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $dailyPredictions = [];
            $totalPredictedAbsences = 0;
            $totalStudents = Student::count();

            foreach ($dayNames as $index => $dayName) {
                $dayPattern = null;
                foreach ($historical['day_of_week_patterns'] as $pattern) {
                    if ($pattern['day'] === $dayName) {
                        $dayPattern = $pattern;
                        break;
                    }
                }

                $expectedAbsences = 0;
                if ($dayPattern) {
                    // Estimate based on historical rate and total students
                    $expectedAbsences = round(($dayPattern['absence_rate'] / 100) * $totalStudents);
                }

                $dailyPredictions[] = [
                    'day' => $dayName,
                    'date' => $startDate->copy()->addDays($index)->toDateString(),
                    'expected_absences' => $expectedAbsences,
                    'historical_absence_rate' => $dayPattern['absence_rate'] ?? 0,
                ];

                $totalPredictedAbsences += $expectedAbsences;
            }

            // Find highest risk days
            $sortedDays = collect($dailyPredictions)->sortByDesc('expected_absences')->take(3);
            $highestRiskDays = $sortedDays->pluck('day')->toArray();

            return [
                'week' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'week_offset' => $weekOffset,
                ],
                'predicted_absences' => $totalPredictedAbsences,
                'daily_predictions' => $dailyPredictions,
                'highest_risk_days' => $highestRiskDays,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Clear prediction cache
     */
    public function clearCache(): void
    {
        Cache::forget('prediction:historical:30');
        Cache::forget('prediction:historical:60');
        Cache::forget('prediction:insights');
        Cache::forget('prediction:weekly:0');
        Cache::forget("prediction:at_risk:30");
        
        // Clear student-specific caches
        Student::query()->select('id')->chunkById(500, function ($students): void {
            foreach ($students as $student) {
                Cache::forget("prediction:student:{$student->id}");
            }
        });
    }

    /**
     * Aggregate attendance stats for a date range in one query.
     */
    private function aggregateAttendanceStats(string $startDate, string $endDate, ?int $studentId = null): array
    {
        $query = AttendanceRecord::whereBetween('date', [$startDate, $endDate])
            ->selectRaw(
                "COUNT(*) as total,
                 SUM(CASE WHEN status IN ('present', 'Present', 'late', 'Late') THEN 1 ELSE 0 END) as present,
                 SUM(CASE WHEN status IN ('absent', 'Absent') THEN 1 ELSE 0 END) as absent"
            );

        if ($studentId !== null) {
            $query->where('student_id', $studentId);
        }

        $row = $query->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'present' => (int) ($row->present ?? 0),
            'absent' => (int) ($row->absent ?? 0),
        ];
    }

    /**
     * Calculate risk score from precomputed metrics.
     */
    private function calculateRiskScoreFromData(array $attendanceStats, int $consecutiveAbsences): int
    {
        $score = 0;
        $baseScore = 100 - $attendanceStats['rate'];
        $score += $baseScore * 0.4;

        if ($attendanceStats['trend'] === 'declining') {
            $score += 25;
        } elseif ($attendanceStats['trend'] === 'improving') {
            $score -= 10;
        }

        if ($consecutiveAbsences >= 3) {
            $score += 30;
        } elseif ($consecutiveAbsences >= 2) {
            $score += 15;
        }

        $absenceRate = $attendanceStats['total'] > 0
            ? ($attendanceStats['absent'] / $attendanceStats['total']) * 100
            : 0;

        if ($absenceRate > 30) {
            $score += 20;
        } elseif ($absenceRate > 20) {
            $score += 10;
        }

        return min(100, max(0, (int) $score));
    }
}

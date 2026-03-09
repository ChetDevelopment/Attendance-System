<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private function getTodayAttendanceTotals(): array
    {
        $today = Carbon::today()->toDateString();

        $counts = AttendanceRecord::query()
            ->selectRaw('status, COUNT(*) as total')
            ->whereDate('attendance_date', $today)
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'present_today' => (int) ($counts['PRESENT'] ?? $counts['present'] ?? 0),
            'absent_today' => (int) ($counts['ABSENT'] ?? $counts['absent'] ?? 0),
            'late_today' => (int) ($counts['LATE'] ?? $counts['late'] ?? 0),
        ];
    }

    public function todayAttendance()
    {
        return response()->json($this->getTodayAttendanceTotals());
    }

    public function presentToday()
    {
        return response()->json([
            'present_today' => $this->getTodayAttendanceTotals()['present_today'],
        ]);
    }

    public function absentToday()
    {
        return response()->json([
            'absent_today' => $this->getTodayAttendanceTotals()['absent_today'],
        ]);
    }

    public function lateToday()
    {
        return response()->json([
            'late_today' => $this->getTodayAttendanceTotals()['late_today'],
        ]);
    }
}

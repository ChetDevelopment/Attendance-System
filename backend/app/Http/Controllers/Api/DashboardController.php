<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Enums\AttendanceStatus;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private function getAttendanceCounts($query): array
    {
        $counts = $query
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'present' => (int) ($counts[AttendanceStatus::PRESENT->value] ?? 0),
            'absent' => (int) ($counts[AttendanceStatus::ABSENT->value] ?? 0),
            'late' => (int) ($counts[AttendanceStatus::LATE->value] ?? 0),
        ];
    }

    private function getTodayAttendanceTotals(): array
    {
        $today = Carbon::today()->toDateString();

        return $this->getAttendanceCounts(
            AttendanceRecord::query()->whereDate('attendance_date', $today)
        );
    }

    private function getWeeklyAttendanceTotals(): array
    {
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $today = Carbon::today()->toDateString();

        return $this->getAttendanceCounts(
            AttendanceRecord::query()
                ->whereBetween('attendance_date', [$startOfWeek, $today])
        );
    }

    private function getMonthlyAttendanceTotals(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $today = Carbon::today()->toDateString();

        return $this->getAttendanceCounts(
            AttendanceRecord::query()
                ->whereBetween('attendance_date', [$startOfMonth, $today])
        );
    }

    private function getTotalAttendanceTotals(): array
    {
        return $this->getAttendanceCounts(AttendanceRecord::query());
    }

    public function todayAttendance()
    {
        $totals = $this->getTodayAttendanceTotals();
        return response()->json([
            'present_today' => $totals['present'],
            'absent_today' => $totals['absent'],
            'late_today' => $totals['late'],
        ]);
    }

    public function weeklyAttendance()
    {
        $totals = $this->getWeeklyAttendanceTotals();
        return response()->json([
            'present_weekly' => $totals['present'],
            'absent_weekly' => $totals['absent'],
            'late_weekly' => $totals['late'],
        ]);
    }

    public function monthlyAttendance()
    {
        $totals = $this->getMonthlyAttendanceTotals();
        return response()->json([
            'present_monthly' => $totals['present'],
            'absent_monthly' => $totals['absent'],
            'late_monthly' => $totals['late'],
        ]);
    }

    public function totalAttendance()
    {
        $totals = $this->getTotalAttendanceTotals();
        return response()->json([
            'present_total' => $totals['present'],
            'absent_total' => $totals['absent'],
            'late_total' => $totals['late'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon; // Make sure Carbon is imported

class DashboardController extends Controller
{
    /**
     * Total Present Today
     */
    public function presentToday()
    {
        $count = Attendance::whereDate('date', Carbon::today())
            ->where('status', 'PRESENT') // Make sure your DB status column uses uppercase
            ->count();

        return response()->json([
            'status' => true,
            'message' => 'Total present today',
            'data' => $count
        ]);
    }

    /**
     * Total Absent Today
     */
    public function absentToday()
    {
        $count = Attendance::whereDate('date', Carbon::today())
            ->where('status', 'ABSENT')
            ->count();

        return response()->json([
            'status' => true,
            'message' => 'Total absent today',
            'data' => $count
        ]);
    }

    /**
     * Total Late Today
     */
    public function lateToday()
    {
        $count = Attendance::whereDate('date', Carbon::today())
            ->where('status', 'LATE')
            ->count();

        return response()->json([
            'status' => true,
            'message' => 'Total late today',
            'data' => $count
        ]);
    }
}
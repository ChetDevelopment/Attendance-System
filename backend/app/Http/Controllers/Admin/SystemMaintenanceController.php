<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemMaintenanceController extends Controller
{
    public function clearCache(Request $request)
    {
        Artisan::call('optimize:clear');

        return response()->json([
            'success' => true,
            'message' => 'System cache cleared.',
            'output' => Artisan::output(),
        ]);
    }

    /**
     * Clear only dashboard-related cache (for refreshing dashboard data)
     */
    public function clearDashboardCache()
    {
        $keys = [
            'admin_dashboard_complete_v2',
            'admin_dashboard_overview_v2',
            'admin_dashboard_summary_v2',
            'admin_quick_stats_v2',
            'admin_student_analytics_v2',
            'admin_class_analytics_v2',
            'admin_system_stats_v2',
            'admin_counts_v1',
            'admin_active_year',
            'admin_active_year_overview',
            'admin_active_year_summary',
            'admin_recent_activities',
            'admin_trends_7days',
            'admin_risk_students_30days',
            'admin_notifications_v2',
        ];

        $cleared = [];
        foreach ($keys as $key) {
            if (Cache::forget($key)) {
                $cleared[] = $key;
            }
        }

        // Also clear date-based cache keys for late students and offsite
        $today = now()->toDateString();
        Cache::forget("admin_late_students_{$today}");
        Cache::forget("admin_offsite_today_{$today}");

        return response()->json([
            'success' => true,
            'message' => 'Dashboard cache cleared.',
            'cleared_keys' => $cleared,
        ]);
    }

    public function exportConfig(Request $request)
    {
        $payload = [
            'exported_at' => now()->toIso8601String(),
            'app_env' => config('app.env'),
            'db' => [
                'connection' => config('database.default'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
            ],
            'sessions' => Session::query()
                ->select('id', 'name', 'start_time', 'end_time', 'order', 'late_threshold', 'is_active', 'description', 'academic_year_id')
                ->orderBy('order')
                ->get()
                ->toArray(),
            'tables' => [
                'users' => Schema::hasTable('users') ? DB::table('users')->count() : null,
                'students' => Schema::hasTable('students') ? DB::table('students')->count() : null,
                'attendance_records' => Schema::hasTable('attendance_records') ? DB::table('attendance_records')->count() : null,
            ],
        ];

        $fileName = 'attendance-config-' . now()->format('Ymd-His') . '.json';

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $fileName, [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }
}




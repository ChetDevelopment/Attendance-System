<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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


<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SystemMaintenanceController extends Controller
{
    private const BACKUP_DISK = 'local';
    private const BACKUP_DIRECTORY = 'system-backups';

    public function __construct(private readonly ActivityLogService $activityLogService)
    {
    }

    public function clearCache(Request $request)
    {
        Artisan::call('optimize:clear');

        $this->activityLogService->recordFromRequest(
            $request->user(),
            $request,
            'Cleared system cache',
            'Cleared application caches from system maintenance'
        );

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

        $this->activityLogService->recordFromRequest(
            $request->user(),
            $request,
            'Exported system config',
            'Downloaded system configuration export'
        );

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $fileName, [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }

    public function listBackups()
    {
        $disk = Storage::disk(self::BACKUP_DISK);
        $files = collect($disk->files(self::BACKUP_DIRECTORY))
            ->filter(fn (string $path) => str_ends_with($path, '.json'))
            ->map(function (string $path) use ($disk) {
                return [
                    'name' => basename($path),
                    'path' => $path,
                    'size' => $disk->size($path),
                    'last_modified' => date('Y-m-d H:i:s', $disk->lastModified($path)),
                ];
            })
            ->sortByDesc('last_modified')
            ->values();

        return response()->json($files);
    }

    public function createBackup(Request $request)
    {
        $payload = $this->buildDatabaseBackupPayload();
        $fileName = 'database-backup-' . now()->format('Ymd-His') . '.json';
        $path = self::BACKUP_DIRECTORY . '/' . $fileName;

        Storage::disk(self::BACKUP_DISK)->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->activityLogService->recordFromRequest(
            $request->user(),
            $request,
            'Created database backup',
            'Created backup file ' . $fileName
        );

        return response()->json([
            'success' => true,
            'message' => 'Database backup created successfully.',
            'backup' => [
                'name' => $fileName,
                'path' => $path,
            ],
        ]);
    }

    public function restoreBackup(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
        ]);

        $path = self::BACKUP_DIRECTORY . '/' . basename($validated['name']);
        $disk = Storage::disk(self::BACKUP_DISK);

        if (!$disk->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Backup file not found.',
            ], 404);
        }

        $payload = json_decode($disk->get($path), true);
        $tables = collect($payload['tables'] ?? []);

        DB::beginTransaction();

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $tables->keys()->reverse()->each(function (string $tableName) {
                DB::table($tableName)->truncate();
            });

            $tables->each(function (array $rows, string $tableName) {
                if (empty($rows)) {
                    return;
                }

                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table($tableName)->insert($chunk);
                }
            });

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();

            $this->activityLogService->recordFromRequest(
                $request->user(),
                $request,
                'Restored database backup',
                'Restored backup file ' . basename($path)
            );

            return response()->json([
                'success' => true,
                'message' => 'Database restored successfully.',
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            return response()->json([
                'success' => false,
                'message' => 'Failed to restore backup.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    private function buildDatabaseBackupPayload(): array
    {
        $tables = collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->filter(fn (string $tableName) => $tableName !== 'migrations')
            ->values();

        $tablePayload = [];

        foreach ($tables as $tableName) {
            $tablePayload[$tableName] = DB::table($tableName)->get()->map(fn ($row) => (array) $row)->all();
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'database' => config('database.connections.' . config('database.default') . '.database'),
            'tables' => $tablePayload,
        ];
    }
}



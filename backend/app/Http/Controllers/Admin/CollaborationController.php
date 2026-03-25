<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollaborationController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {
    }

    /**
     * Get shared activity feed for all roles
     * Shows activities from Admin, Education, and Teacher
     */
    public function activityFeed(Request $request)
    {
        $limit = $request->input('limit', 20);
        $role = $request->input('role'); // Filter by role if needed

        $query = ActivityLog::query()
            ->with('user:id,name,email,role_id')
            ->with('user.role:id,name,slug')
            ->orderByDesc('created_at')
            ->limit($limit);

        // Filter by specific role if requested
        if ($role) {
            $query->whereHas('user.role', function ($q) use ($role) {
                $q->where('slug', $role);
            });
        }

        $activities = $query->get()->map(function (ActivityLog $log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user_name' => $log->user_name ?? $log->user?->name ?? 'System',
                'user_role' => $log->user?->role?->name ?? 'Unknown',
                'user_role_slug' => $log->user?->role?->slug ?? 'unknown',
                'created_at' => $log->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'activities' => $activities,
        ]);
    }

    /**
     * Get team members across all roles
     */
    public function teamMembers(Request $request)
    {
        $roleFilter = $request->input('role');

        $query = User::query()
            ->with('role:id,name,slug')
            ->where('is_active', true)
            ->orderBy('name');

        if ($roleFilter) {
            $query->whereHas('role', function ($q) use ($roleFilter) {
                $q->where('slug', $roleFilter);
            });
        }

        $members = $query->get()->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name ?? 'Unknown',
                'role_slug' => $user->role?->slug ?? 'unknown',
                'photo' => $user->profile_image ?? $user->avatar_url,
            ];
        });

        return response()->json([
            'members' => $members,
        ]);
    }

    /**
     * Create a cross-role request
     * Teachers can request from Education, Education can request from Admin
     */
    public function createRequest(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:support,approval,escalation,feedback'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'target_role' => ['required', 'string', 'in:education,admin'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
        ]);

        $currentUser = $request->user();
        $currentRole = strtolower((string) optional($currentUser->role)->slug);

        // Determine target role ID
        $targetRoleId = match ($validated['target_role']) {
            'education' => 3, // Education Team role ID
            'admin' => 1,     // Admin role ID
            default => null,
        };

        // Create activity log entry as the request
        $this->activityLogService->recordFromRequest(
            $currentUser,
            $request,
            'Request: ' . $validated['type'],
            $validated['title'] . ' - ' . substr($validated['description'], 0, 100)
        );

        // Store request in activity logs for tracking
        $priority = $validated['priority'] ?? 'medium';
        
        return response()->json([
            'success' => true,
            'message' => 'Request sent successfully to ' . ucfirst($validated['target_role']),
            'request' => [
                'type' => $validated['type'],
                'title' => $validated['title'],
                'target_role' => $validated['target_role'],
                'priority' => $priority,
                'created_by' => $currentUser->name,
                'created_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get pending requests for Education or Admin
     */
    public function pendingRequests(Request $request)
    {
        $currentUser = $request->user();
        $roleSlug = strtolower((string) optional($currentUser->role)->slug);

        // Get recent activity logs that could be considered requests
        // Filter to show only requests from other roles
        $requests = ActivityLog::query()
            ->with('user:id,name,role_id')
            ->with('user.role:id,name,slug')
            ->where('action', 'like', 'Request:%')
            ->where('user_id', '!=', $currentUser->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function (ActivityLog $log) {
                return [
                    'id' => $log->id,
                    'type' => str_replace('Request: ', '', $log->action),
                    'title' => $log->description,
                    'description' => $log->description,
                    'from_name' => $log->user_name ?? $log->user?->name ?? 'Unknown',
                    'from_role' => $log->user?->role?->name ?? 'Unknown',
                    'to_role' => 'Education Team',
                    'priority' => 'medium',
                    'status' => 'pending',
                    'created_at' => $log->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'requests' => $requests,
        ]);
    }

    /**
     * Resolve a cross-role request (approve/reject)
     */
    public function resolveRequest(Request $request, int $id)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected'],
        ]);

        $currentUser = $request->user();
        
        // Find the activity log (request)
        $activityLog = ActivityLog::findOrFail($id);

        // Log the resolution action
        $this->activityLogService->recordFromRequest(
            $currentUser,
            $request,
            'Request Resolution: ' . $request->input('status'),
            'Request "' . $activityLog->description . '" was ' . $request->input('status')
        );

        return response()->json([
            'success' => true,
            'message' => 'Request has been ' . $request->input('status'),
            'request_id' => $id,
            'status' => $request->input('status'),
        ]);
    }

    /**
     * Get collaboration stats - overview of all roles' activities
     */
    public function collaborationStats()
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();

        // Count users by role
        $roleCounts = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.is_active', true)
            ->select('roles.slug', DB::raw('COUNT(*) as count'))
            ->groupBy('roles.slug')
            ->pluck('count', 'slug')
            ->toArray();

        // Activity counts by role this week
        $activityByRole = ActivityLog::query()
            ->join('users', 'activity_logs.user_id', '=', 'users.id')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('activity_logs.created_at', '>=', $thisWeek)
            ->select('roles.slug', DB::raw('COUNT(*) as count'))
            ->groupBy('roles.slug')
            ->pluck('count', 'slug')
            ->toArray();

        return response()->json([
            'active_users' => [
                'admin' => $roleCounts['admin'] ?? 0,
                'teacher' => $roleCounts['teacher'] ?? 0,
                'education_team' => $roleCounts['education_team'] ?? 0,
            ],
            'weekly_activity' => [
                'admin' => $activityByRole['admin'] ?? 0,
                'teacher' => $activityByRole['teacher'] ?? 0,
                'education_team' => $activityByRole['education_team'] ?? 0,
            ],
            'total_active' => array_sum($roleCounts),
        ]);
    }

    /**
     * Quick stats for dashboard widgets
     */
    public function quickStats()
    {
        $today = now()->startOfDay();

        // Today's activity summary
        $todayActivity = ActivityLog::query()
            ->where('created_at', '>=', $today)
            ->count();

        // Recent activity for quick view
        $recentActivities = ActivityLog::query()
            ->with('user:id,name')
            ->with('user.role:id,name,slug')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function (ActivityLog $log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user' => $log->user?->name ?? 'System',
                    'role' => $log->user?->role?->name ?? 'System',
                    'time' => $log->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'today_activity_count' => $todayActivity,
            'recent_activities' => $recentActivities,
        ]);
    }
}
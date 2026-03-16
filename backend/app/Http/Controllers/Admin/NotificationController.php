<?php

namespace App\Http\Controllers;

use App\Models\AbsenceNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        
        // Get notifications based on user role
        $query = AbsenceNotification::with(['student', 'session']);
        
        // If user is a student, only show their notifications
        if ($user->student_id) {
            $query->where('student_id', $user->student_id);
        }
        
        $notifications = $query
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->notification_type ?? 'absence',
                    'title' => $this->getNotificationTitle($notification),
                    'message' => $notification->absence_reason ?? 'You have an absence notification',
                    'status' => $notification->status,
                    'student_id' => $notification->student_id,
                    'session_id' => $notification->session_id,
                    'date' => $notification->created_at->toDateString(),
                    'created_at' => $notification->created_at,
                    'read' => $notification->status !== 'pending',
                ];
            });
        
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifications->where('read', false)->count(),
        ]);
    }
    
    /**
     * Get notification title based on type
     */
    private function getNotificationTitle($notification)
    {
        $titles = [
            'absence_alert' => 'Absence Alert',
            'absence_warning' => 'Absence Warning',
            'attendance_reminder' => 'Attendance Reminder',
            'low_attendance' => 'Low Attendance Notice',
        ];
        
        return $titles[$notification->notification_type] ?? 'Notification';
    }
    
    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = AbsenceNotification::findOrFail($id);
        
        $notification->update([
            'status' => 'read'
        ]);
        
        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification,
        ]);
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        
        $query = AbsenceNotification::where('status', 'pending');
        
        // If user is a student, only update their notifications
        if ($user->student_id) {
            $query->where('student_id', $user->student_id);
        }
        
        $query->update(['status' => 'read']);
        
        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }
    
    /**
     * Delete a notification
     */
    public function destroy(Request $request, $id)
    {
        $notification = AbsenceNotification::findOrFail($id);
        
        // Check if user owns this notification (for students)
        $user = $request->user();
        if ($user && $user->student_id && $notification->student_id !== $user->student_id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $notification->delete();
        
        return response()->json([
            'message' => 'Notification deleted',
        ]);
    }
    
    /**
     * Get unread notification count
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'count' => 0
            ]);
        }
        
        $query = AbsenceNotification::where('status', 'pending');
        
        if ($user->student_id) {
            $query->where('student_id', $user->student_id);
        }
        
        $count = $query->count();
        
        return response()->json([
            'count' => $count
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TeacherPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $userName = $request->user()->name;
            $today = Carbon::today();

            // Get all sessions for today (the system doesn't track which teacher teaches which session)
            // So we show sessions from attendance records submitted by this teacher
            $todayClasses = DB::table('attendance_records as ar')
                ->join('sessions as se', 'se.id', '=', 'ar.session_id')
                ->join('students as st', 'st.id', '=', 'ar.student_id')
                ->leftJoin('classes as c', 'c.id', '=', 'st.class_id')
                ->where('ar.submitted_by', $userId)
                ->whereDate('ar.created_at', $today)
                ->groupBy('se.id', 'se.name', 'se.start_time', 'se.end_time', 'c.class_name', 'st.class')
                ->orderBy('se.start_time')
                ->selectRaw("se.id, se.name as subject, COALESCE(c.class_name, st.class, 'Unknown') as classCode, se.start_time, se.end_time")
                ->get();

            // If no attendance records yet, show available sessions
            if ($todayClasses->isEmpty()) {
                $todayClasses = DB::table('sessions')
                    ->select('id', 'name as subject', 'start_time', 'end_time')
                    ->orderBy('start_time')
                    ->get()
                    ->map(function($s) {
                        $s->classCode = 'Not Assigned';
                        return $s;
                    });
            }

            $now = Carbon::now()->format('H:i:s');
            $active = $todayClasses->first(fn ($c) => $c->start_time <= $now && $c->end_time >= $now);
            $next = $todayClasses->first(fn ($c) => $c->start_time > $now);

            $checkedInCount = DB::table('attendance_records')
                ->where('submitted_by', $userId)
                ->whereDate('created_at', $today)
                ->where('status', 'Present')
                ->count();

            $absentCount = DB::table('attendance_records')
                ->where('submitted_by', $userId)
                ->whereDate('created_at', $today)
                ->where('status', 'Absent')
                ->count();

            // Get recent teacher activity count
            $activityCount = 0;
            if (Schema::hasTable('teacher_activities')) {
                $activityCount = DB::table('teacher_activities')
                    ->where('user_id', $userId)
                    ->count();
            }

            return response()->json([
                'today_classes' => $todayClasses ?? [],
                'active' => $active ?? null,
                'next_today' => $next ?? null,
                'checked_in_count' => $checkedInCount ?? 0,
                'absent_count' => $absentCount ?? 0,
                'activity_count' => $activityCount,
                'teacher_name' => $userName,
            ]);
        } catch (\Exception $e) {
            \Log::error('Teacher dashboard error: ' . $e->getMessage());
            return response()->json([
                'today_classes' => [],
                'active' => null,
                'next_today' => null,
                'checked_in_count' => 0,
                'absent_count' => 0,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function schedule(Request $request)
    {
        try {
            // Cache for 5 minutes
            $cacheKey = 'teacher_schedule_' . $request->user()->id;
            
            return Cache::remember($cacheKey, 300, function () {
                $teachers = DB::table('users as u')
                    ->join('roles as r', 'r.id', '=', 'u.role_id')
                    ->whereRaw('LOWER(r.name) = ?', ['teacher'])
                    ->select('u.id', 'u.name')
                    ->orderBy('u.name')
                    ->get();

                $sessions = DB::table('sessions')
                    ->select('id', 'name', 'start_time', 'end_time', 'order')
                    ->orderBy('start_time')
                    ->get();

                return response()->json([
                    'teachers' => $teachers ?? [],
                    'sessions' => $sessions ?? [],
                ]);
            });
        } catch (\Exception $e) {
            \Log::error('Teacher schedule error: ' . $e->getMessage());
            return response()->json([
                'teachers' => [],
                'sessions' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function justifications(Request $request)
    {
        try {
            if (! Schema::hasTable('attendance_follow_ups')) {
                return response()->json([]);
            }

            $rows = DB::table('attendance_follow_ups as af')
                ->join('attendance_records as ar', 'ar.id', '=', 'af.attendance_record_id')
                ->join('students as st', 'st.id', '=', 'ar.student_id')
                ->leftJoin('sessions as se', 'se.id', '=', 'ar.session_id')
                ->leftJoin('classes as c', 'c.id', '=', 'st.class_id')
                ->orderByDesc('af.created_at')
                ->limit(50)
                ->selectRaw(
                    "CONCAT('j-', af.id) as id,
                    st.fullname as studentName,
                    st.username as studentId,
                    st.profile as studentPhoto,
                    COALESCE(c.class_name, st.class, 'Unknown') as classCode,
                    COALESCE(se.name, 'Session') as subject,
                    COALESCE(af.comment, af.note, af.reason, '') as educationComment,
                    DATE(ar.created_at) as date,
                    DATE_FORMAT(af.created_at, '%Y-%m-%d %h:%i %p') as timestamp"
                )
                ->get();

            return response()->json($rows ?? []);
        } catch (\Exception $e) {
            \Log::error('Teacher justifications error: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    public function history(Request $request)
    {
        try {
            $rows = DB::table('attendance_records as ar')
                ->join('sessions as se', 'se.id', '=', 'ar.session_id')
                ->join('students as st', 'st.id', '=', 'ar.student_id')
                ->leftJoin('classes as c', 'c.id', '=', 'st.class_id')
                ->where('ar.submitted_by', $request->user()->id)
                ->groupBy(DB::raw('DATE(ar.created_at)'), 'se.id', 'se.name', 'se.start_time', 'se.end_time', 'c.class_name', 'st.class')
                ->orderByDesc(DB::raw('DATE(ar.created_at)'))
                ->selectRaw(
                    "MIN(ar.id) as id,
                    DATE(ar.created_at) as date,
                    se.name as subject,
                    COALESCE(c.class_name, st.class, 'Unknown') as classCode,
                    DATE_FORMAT(se.start_time, '%H:%i') as startTime,
                    DATE_FORMAT(se.end_time, '%H:%i') as endTime,
                    SUM(CASE WHEN ar.status = 'Present' THEN 1 ELSE 0 END) as presentCount,
                    SUM(CASE WHEN ar.status = 'Absent' THEN 1 ELSE 0 END) as absentCount,
                    SUM(CASE WHEN ar.status = 'Late' THEN 1 ELSE 0 END) as lateCount,
                    COUNT(*) as totalStudents,
                    ROUND(SUM(CASE WHEN ar.status = 'Present' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 0) as attendanceRate"
                )
                ->limit(100)
                ->get();

            return response()->json($rows ?? []);
        } catch (\Exception $e) {
            \Log::error('Teacher history error: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    public function students()
    {
        try {
            // Cache for 2 minutes to improve performance
            return Cache::remember('all_students_list', 120, function () {
                return response()->json(
                    DB::table('students')
                        ->select('id', 'fullname as name', 'username as student_code', 'email', 'profile as avatar', 'class', 'contact')
                        ->orderBy('fullname')
                        ->get()
                );
            });
        } catch (\Exception $e) {
            \Log::error('Teacher students error: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    public function notifications(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $notifications = collect();

            // Get teacher activities if table exists
            if (Schema::hasTable('teacher_activities')) {
                $activities = DB::table('teacher_activities')
                    ->where('user_id', $userId)
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => 'activity-' . $item->id,
                            'title' => 'Activity Update',
                            'message' => $item->action,
                            'time' => Carbon::parse($item->created_at)->diffForHumans(),
                            'type' => 'system',
                            'unread' => false,
                            'created_at' => $item->created_at,
                        ];
                    });
                $notifications = $notifications->concat($activities);
            }

            // Also get absence notifications for this teacher's students
            if (Schema::hasTable('absence_notifications')) {
                // Get students in this teacher's classes
                $studentIds = DB::table('students')
                    ->where('assigned_teacher_id', $userId)
                    ->orWhere('class_teacher_id', $userId)
                    ->pluck('id');

                if ($studentIds->isNotEmpty()) {
                    $absenceNotifs = DB::table('absence_notifications')
                        ->whereIn('student_id', $studentIds)
                        ->orderByDesc('created_at')
                        ->limit(10)
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => 'absence-' . $item->id,
                                'title' => 'Absence Alert',
                                'message' => $item->absence_reason ?? 'Student absence notification',
                                'time' => Carbon::parse($item->created_at)->diffForHumans(),
                                'type' => 'warning',
                                'unread' => $item->status === 'pending',
                                'created_at' => $item->created_at,
                            ];
                        });
                    $notifications = $notifications->concat($absenceNotifs);
                }
            }

            // Sort by created_at descending and return
            return response()->json(
                $notifications->sortByDesc('created_at')->values()->take(30)->all()
            );
        } catch (\Exception $e) {
            \Log::error('Teacher notifications error: ' . $e->getMessage());
            return response()->json([]);
        }
    }
}

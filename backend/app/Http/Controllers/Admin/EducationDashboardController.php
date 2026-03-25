<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbsenceNotification;
use App\Models\AttendanceFollowUp;
use App\Models\AttendanceRecord;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EducationDashboardController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService)
    {
    }

    public function stats()
    {
        $today = Carbon::today()->toDateString();

        $absentToday = AbsenceNotification::query()
            ->where('status', 'active')
            ->where('absence_status', AbsenceNotification::STATUS_PENDING)
            ->whereHas('attendanceRecord', function ($query) use ($today) {
                $query->whereDate('attendance_date', $today)
                    ->orWhere(function ($fallback) use ($today) {
                        $fallback->whereNull('attendance_date')
                            ->whereDate('date', $today);
                    });
            })
            ->count();

        $lateToday = AttendanceRecord::query()
            ->where(function ($query) use ($today) {
                $query->whereDate('attendance_date', $today)
                    ->orWhere(function ($fallback) use ($today) {
                        $fallback->whereNull('attendance_date')
                            ->whereDate('date', $today);
                    });
            })
            ->whereRaw('LOWER(status) = ?', ['late'])
            ->count();

        $highRisk = AttendanceRecord::query()
            ->select('student_id')
            ->where(function ($query) {
                $query->whereDate('attendance_date', '>=', now()->subDays(30)->toDateString())
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('attendance_date')
                            ->whereDate('date', '>=', now()->subDays(30)->toDateString());
                    });
            })
            ->whereRaw('LOWER(status) = ?', ['absent'])
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) >= 3')
            ->get()
            ->count();

        $pendingFollowUp = AbsenceNotification::query()
            ->where('status', 'active')
            ->where('absence_status', AbsenceNotification::STATUS_PENDING)
            ->count();

        return response()->json([
            'absentToday' => $absentToday,
            'lateToday' => $lateToday,
            'highRisk' => $highRisk,
            'pendingFollowUp' => $pendingFollowUp,
        ]);
    }

    public function absentToday()
    {
        $rows = AbsenceNotification::query()
            ->with([
                'student:id,fullname,class,class_id',
                'student.schoolClass:id,name',
                'attendanceRecord:id,attendance_date,date,status,submitted_by,session_id',
            ])
            ->where('status', 'active')
            ->whereHas('attendanceRecord', function ($query) {
                $query->whereDate('attendance_date', today())
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('attendance_date')
                            ->whereDate('date', today());
                    });
            })
            ->latest()
            ->get()
            ->map(function (AbsenceNotification $absence) {
                $attendanceDate = optional($absence->attendanceRecord?->attendance_date ?? $absence->attendanceRecord?->date)?->toDateString()
                    ?? optional($absence->created_at)->toDateString();

                return [
                    'attendance_id' => $absence->attendance_record_id ?: $absence->id,
                    'name' => $absence->student?->fullname ?? 'Unknown Student',
                    'class' => $absence->student?->schoolClass?->name ?? $absence->student?->class ?? 'Unknown Class',
                    'date' => $attendanceDate,
                    'resolved' => strtoupper((string) $absence->absence_status) !== 'PENDING',
                ];
            });

        return response()->json($rows);
    }

    public function allAbsent()
    {
        $rows = AbsenceNotification::query()
            ->with('student:id,fullname,class,class_id')
            ->with('student.schoolClass:id,name')
            ->with('attendanceRecord:id,attendance_date,date,status,submitted_by,session_id')
            ->where('status', 'active')
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (AbsenceNotification $absence) {
                $attendanceDate = optional($absence->attendanceRecord?->attendance_date ?? $absence->attendanceRecord?->date)?->toDateString()
                    ?? optional($absence->created_at)->toDateString();

                return [
                    'attendance_id' => $absence->attendance_record_id ?: $absence->id,
                    'name' => $absence->student?->fullname ?? 'Unknown Student',
                    'class' => $absence->student?->schoolClass?->name ?? $absence->student?->class ?? 'Unknown Class',
                    'date' => $attendanceDate,
                    'reason' => $absence->absence_reason,
                    'resolved' => strtoupper((string) $absence->absence_status) !== 'PENDING',
                ];
            });

        return response()->json($rows);
    }

    public function riskStudents()
    {
        $rows = AttendanceRecord::query()
            ->select('student_id', DB::raw('COUNT(*) as absence_count'), DB::raw('MAX(id) as latest_attendance_id'))
            ->where(function ($query) {
                $query->whereDate('attendance_date', '>=', now()->subDays(30)->toDateString())
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('attendance_date')
                            ->whereDate('date', '>=', now()->subDays(30)->toDateString());
                    });
            })
            ->whereRaw('LOWER(status) = ?', ['absent'])
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) >= 3')
            ->with('student:id,fullname,class,class_id')
            ->with('student.schoolClass:id,name')
            ->orderByDesc('absence_count')
            ->limit(20)
            ->get()
            ->map(function (AttendanceRecord $record) {
                return [
                    'name' => $record->student?->fullname ?? 'Unknown Student',
                    'class' => $record->student?->schoolClass?->name ?? $record->student?->class ?? 'Unknown Class',
                    'absence_count' => (int) $record->absence_count,
                    'latest_attendance_id' => (int) $record->latest_attendance_id,
                ];
            });

        return response()->json($rows);
    }

    public function classReports()
    {
        $classExpression = "COALESCE(c.name, c.class_name, s.class, 'Unknown Class')";

        $rows = DB::table('attendance_records as ar')
            ->join('students as s', 's.id', '=', 'ar.student_id')
            ->leftJoin('classes as c', 'c.id', '=', 's.class_id')
            ->selectRaw("
                {$classExpression} as class,
                SUM(CASE WHEN LOWER(ar.status) = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN LOWER(ar.status) = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN LOWER(ar.status) = 'late' THEN 1 ELSE 0 END) as late_count
            ")
            ->groupByRaw($classExpression)
            ->orderByRaw($classExpression)
            ->get();

        return response()->json($rows);
    }

    public function attendanceDetail(int $id)
    {
        $absence = AbsenceNotification::query()
            ->with([
                'student.schoolClass:id,name',
                'session',
                'attendanceRecord.teacher:id,name',
                'attendanceRecord.session:id,name,start_time,end_time',
            ])
            ->where('id', $id)
            ->orWhere('attendance_record_id', $id)
            ->latest('id')
            ->first();

        if (!$absence) {
            return response()->json([
                'message' => 'Attendance detail not found.',
            ], 404);
        }

        $attendanceDate = $absence->attendanceRecord?->attendance_date?->toDateString()
            ?? optional($absence->attendanceRecord?->date)->toDateString()
            ?? optional($absence->created_at)->toDateString();

        $followUps = AttendanceFollowUp::query()
            ->where('attendance_record_id', $absence->attendance_record_id)
            ->latest()
            ->get()
            ->map(fn (AttendanceFollowUp $followUp) => [
                'updated_by' => optional($followUp->updatedBy)->name ?? 'System',
                'status' => $followUp->status,
                'resolved' => (bool) $followUp->resolved,
                'timestamp' => optional($followUp->created_at)->toIso8601String(),
                'comment' => $followUp->comment,
                'note' => $followUp->note,
            ]);

        return response()->json([
            'id' => $absence->attendance_record_id ?: $absence->id,
            'name' => $absence->student?->fullname ?? 'Unknown Student',
            'class' => $absence->student?->schoolClass?->name ?? $absence->student?->class ?? 'Unknown Class',
            'date' => $attendanceDate,
            'contact_info' => $absence->student?->parent_number ?: $absence->student?->contact ?: 'No contact available',
            'reason' => $absence->absence_reason ?? '',
            'status' => strtoupper((string) $absence->absence_status),
            'resolved' => strtoupper((string) $absence->absence_status) !== 'PENDING',
            'is_excused' => strtoupper((string) $absence->absence_status) === 'EXCUSED' ? 1 : 0,
            'marked_by' => $absence->attendanceRecord?->teacher?->name ?? 'System',
            'session_name' => $absence->session?->name ?? $absence->attendanceRecord?->session?->name,
            'session_time' => $absence->session
                ? trim(($absence->session->start_time ?? '') . ' - ' . ($absence->session->end_time ?? ''))
                : trim(($absence->attendanceRecord?->session?->start_time ?? '') . ' - ' . ($absence->attendanceRecord?->session?->end_time ?? '')),
            'followUps' => $followUps,
        ]);
    }

    public function submitFollowUp(Request $request)
    {
        $validated = $request->validate([
            'attendanceId' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:100'],
            'resolved' => ['nullable', 'boolean'],
            'isExcused' => ['nullable', 'boolean'],
        ]);

        $absence = AbsenceNotification::query()
            ->where('id', $validated['attendanceId'])
            ->orWhere('attendance_record_id', $validated['attendanceId'])
            ->latest('id')
            ->first();

        if (!$absence || !$absence->attendance_record_id) {
            return response()->json(['message' => 'Absence record not found.'], 404);
        }

        AttendanceFollowUp::create([
            'attendance_record_id' => $absence->attendance_record_id,
            'updated_by' => $request->user()?->id,
            'reason' => $validated['reason'] ?? 'Education follow-up',
            'comment' => $validated['comment'] ?? null,
            'note' => $validated['note'] ?? null,
            'status' => $validated['status'] ?? 'In Progress',
            'resolved' => (bool) ($validated['resolved'] ?? false),
            'is_excused' => (bool) ($validated['isExcused'] ?? false),
        ]);

        $absence->update([
            'absence_reason' => $validated['reason'] ?: $absence->absence_reason,
            'comment' => $validated['comment'] ?: $absence->comment,
            'follow_up_notes' => $validated['note'] ?: $absence->follow_up_notes,
            'absence_status' => ($validated['isExcused'] ?? false) ? 'EXCUSED' : (($validated['resolved'] ?? false) ? 'UNEXCUSED' : 'PENDING'),
            'status_updated_by' => $request->user()?->id,
            'status_updated_at' => now(),
        ]);

        $this->activityLogService->recordFromRequest(
            $request->user(),
            $request,
            'Education follow-up updated',
            'Updated follow-up for attendance record #' . $absence->attendance_record_id
        );

        return response()->json(['success' => true, 'message' => 'Follow-up saved successfully.']);
    }

    public function sendAlert(Request $request)
    {
        $validated = $request->validate([
            'attendanceId' => ['required', 'integer'],
            'studentName' => ['nullable', 'string', 'max:255'],
            'className' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
        ]);

        $this->activityLogService->recordFromRequest(
            $request->user(),
            $request,
            'Request: escalation',
            trim(
                'Education escalation for attendance record #' . $validated['attendanceId']
                . ($validated['studentName'] ? ' - ' . $validated['studentName'] : '')
                . ($validated['className'] ? ' (' . $validated['className'] . ')' : '')
                . ($validated['date'] ? ' on ' . $validated['date'] : '')
            )
        );

        return response()->json([
            'success' => true,
            'message' => 'Alert sent to the shared Teacher/Admin activity stream.',
        ]);
    }
}

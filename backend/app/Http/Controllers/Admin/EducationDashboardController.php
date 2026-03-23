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
        $today = Carbon::today();

        $absentToday = AttendanceRecord::query()
            ->whereDate('attendance_date', $today)
            ->whereRaw('LOWER(status) = ?', ['absent'])
            ->count();

        $lateToday = AttendanceRecord::query()
            ->whereDate('attendance_date', $today)
            ->whereRaw('LOWER(status) = ?', ['late'])
            ->count();

        $highRisk = AttendanceRecord::query()
            ->select('student_id')
            ->whereDate('attendance_date', '>=', now()->subDays(30)->toDateString())
            ->whereRaw('LOWER(status) = ?', ['absent'])
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) >= 3')
            ->get()
            ->count();

        $pendingFollowUp = AttendanceFollowUp::query()
            ->where('resolved', false)
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
            ->with(['student:id,fullname,class,class_id', 'student.schoolClass:id,name', 'attendanceRecord:id'])
            ->where('status', 'active')
            ->whereDate('created_at', today())
            ->latest()
            ->get()
            ->map(function (AbsenceNotification $absence) {
                return [
                    'attendance_id' => $absence->attendance_record_id ?: $absence->id,
                    'name' => $absence->student?->fullname ?? 'Unknown Student',
                    'class' => $absence->student?->schoolClass?->name ?? $absence->student?->class ?? 'Unknown Class',
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
            ->where('status', 'active')
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (AbsenceNotification $absence) {
                return [
                    'attendance_id' => $absence->attendance_record_id ?: $absence->id,
                    'name' => $absence->student?->fullname ?? 'Unknown Student',
                    'class' => $absence->student?->schoolClass?->name ?? $absence->student?->class ?? 'Unknown Class',
                    'date' => optional($absence->created_at)->toDateString(),
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
            ->whereDate('attendance_date', '>=', now()->subDays(30)->toDateString())
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
        $rows = DB::table('attendance_records as ar')
            ->join('students as s', 's.id', '=', 'ar.student_id')
            ->leftJoin('classes as c', 'c.id', '=', 's.class_id')
            ->selectRaw("
                COALESCE(c.name, c.class_name, s.class, 'Unknown Class') as class,
                SUM(CASE WHEN LOWER(ar.status) = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN LOWER(ar.status) = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN LOWER(ar.status) = 'late' THEN 1 ELSE 0 END) as late_count
            ")
            ->groupBy('class')
            ->orderBy('class')
            ->get();

        return response()->json($rows);
    }

    public function attendanceDetail(int $id)
    {
        $absence = AbsenceNotification::query()
            ->with(['student', 'session'])
            ->where('id', $id)
            ->orWhere('attendance_record_id', $id)
            ->latest('id')
            ->first();

        if (!$absence) {
            return response()->json([
                'message' => 'Attendance detail not found.',
            ], 404);
        }

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
            'date' => optional($absence->created_at)->toDateString(),
            'contact_info' => $absence->student?->parent_number ?: $absence->student?->contact ?: 'No contact available',
            'reason' => $absence->absence_reason ?? '',
            'is_excused' => strtoupper((string) $absence->absence_status) === 'EXCUSED' ? 1 : 0,
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
        ]);

        $this->activityLogService->recordFromRequest(
            $request->user(),
            $request,
            'Education alert sent',
            'Sent alert for attendance record #' . $validated['attendanceId'] . ($validated['studentName'] ? ' (' . $validated['studentName'] . ')' : '')
        );

        return response()->json([
            'success' => true,
            'message' => 'Alert recorded successfully.',
        ]);
    }
}

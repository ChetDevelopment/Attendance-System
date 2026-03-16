<?php

namespace App\Http\Controllers;

use App\Models\AbsenceNotification;
use App\Models\AttendanceRecord;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AbsenceManagementController extends Controller
{
    /**
     * List all absences with filters.
     * GET /api/admin/absences
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|string|in:PENDING,EXCUSED,UNEXCUSED',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'class_id' => 'nullable|integer|exists:classes,id',
            'student_id' => 'nullable|integer|exists:students,id',
            'search' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $query = DB::table('absence_notifications as an')
                ->join('students as s', 's.id', '=', 'an.student_id')
                ->leftJoin('classes as c', 'c.id', '=', 's.class_id')
                ->leftJoin('sessions as sess', 'sess.id', '=', 'an.session_id')
                ->leftJoin('attendance_records as ar', 'ar.id', '=', 'an.attendance_record_id')
                ->selectRaw("
                    an.id,
                    an.student_id,
                    s.fullname as student_name,
                    s.username as student_code,
                    COALESCE(c.class_name, s.class, 'Unknown') as class_name,
                    s.class_id,
                    DATE(an.created_at) as absence_date,
                    sess.name as session_name,
                    sess.start_time as session_time,
                    an.absence_reason,
                    an.absence_status,
                    an.comment,
                    an.follow_up_notes,
                    an.reason_submitted_at,
                    an.status_updated_at,
                    ar.status as attendance_status
                ");

            if ($request->filled('status')) {
                $query->where('an.absence_status', $request->status);
            }

            if ($request->filled('start_date')) {
                $query->whereDate('an.created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('an.created_at', '<=', $request->end_date);
            }

            if ($request->filled('class_id')) {
                $query->where('s.class_id', $request->class_id);
            }

            if ($request->filled('student_id')) {
                $query->where('an.student_id', $request->student_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('s.fullname', 'like', "%{$search}%")
                        ->orWhere('s.username', 'like', "%{$search}%");
                });
            }

            $results = $query
                ->orderByDesc('an.created_at')
                ->paginate($request->input('per_page', 20));

            return response()->json($results);
        } catch (\Throwable $exception) {
            Log::error('Failed to load absence index', [
                'filters' => $request->only(['status', 'start_date', 'end_date', 'class_id', 'student_id', 'search']),
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to load absences at this time.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * View absence details.
     * GET /api/admin/absences/{id}
     */
    public function show(int $id)
    {
        $absence = AbsenceNotification::with([
            'student',
            'session',
            'attendanceRecord',
            'statusUpdatedBy',
            'reasonSubmittedBy',
        ])->find($id);

        if (!$absence) {
            return response()->json([
                'message' => 'Absence notification not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Get related attendance record details
        $attendanceDetails = null;
        if ($absence->attendance_record_id) {
            $attendanceDetails = DB::table('attendance_records as ar')
                ->leftJoin('users as teacher', 'teacher.id', '=', 'ar.created_by')
                ->where('ar.id', $absence->attendance_record_id)
                ->selectRaw("
                    ar.id,
                    ar.status,
                    ar.date,
                    ar.location,
                    ar.justification,
                    ar.justified_at,
                    COALESCE(teacher.name, 'System') as marked_by
                ")
                ->first();
        }

        // Get follow-ups for this absence
        $followUps = DB::table('attendance_follow_ups as af')
            ->leftJoin('users as u', 'u.id', '=', 'af.updated_by')
            ->where('af.attendance_record_id', $absence->attendance_record_id)
            ->orderByDesc('af.created_at')
            ->selectRaw("
                af.id,
                COALESCE(u.name, 'System') as updated_by,
                af.reason,
                af.comment,
                af.note,
                af.status as follow_up_status,
                af.resolved,
                af.is_excused,
                af.created_at
            ")
            ->get();

        return response()->json([
            'id' => $absence->id,
            'student' => [
                'id' => $absence->student->id,
                'name' => $absence->student->fullname,
                'code' => $absence->student->username,
                'class' => $absence->student->class,
                'class_id' => $absence->student->class_id,
                'parent_number' => $absence->student->parent_number,
            ],
            'session' => $absence->session ? [
                'id' => $absence->session->id,
                'name' => $absence->session->name,
                'start_time' => $absence->session->start_time,
                'end_time' => $absence->session->end_time,
            ] : null,
            'absence_reason' => $absence->absence_reason,
            'reason_submitted_by' => $absence->reasonSubmittedBy ? $absence->reasonSubmittedBy->name : null,
            'reason_submitted_at' => $absence->reason_submitted_at,
            'absence_status' => $absence->absence_status,
            'comment' => $absence->comment,
            'follow_up_notes' => $absence->follow_up_notes,
            'status_updated_by' => $absence->statusUpdatedBy ? $absence->statusUpdatedBy->name : null,
            'status_updated_at' => $absence->status_updated_at,
            'notification_status' => $absence->status,
            'created_at' => $absence->created_at,
            'attendance' => $attendanceDetails,
            'follow_ups' => $followUps,
        ]);
    }

    /**
     * Update absence reason.
     * PUT /api/admin/absences/{id}/reason
     */
    public function updateReason(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        $absence = AbsenceNotification::find($id);

        if (!$absence) {
            return response()->json([
                'message' => 'Absence notification not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $userId = $request->user()->id ?? null;

        $absence->update([
            'absence_reason' => $request->reason,
            'reason_submitted_by' => $userId,
            'reason_submitted_at' => now(),
        ]);

        // Also update the attendance record justification if exists
        if ($absence->attendance_record_id) {
            AttendanceRecord::where('id', $absence->attendance_record_id)->update([
                'justification' => $request->reason,
                'justified_at' => now(),
                'justified_by' => $userId,
            ]);
        }

        Log::info('Absence reason updated', [
            'absence_id' => $id,
            'reason' => $request->reason,
            'updated_by' => $userId,
        ]);

        return response()->json([
            'message' => 'Absence reason updated successfully',
            'data' => [
                'id' => $absence->id,
                'absence_reason' => $absence->absence_reason,
                'reason_submitted_at' => $absence->reason_submitted_at,
            ],
        ]);
    }

    /**
     * Add comment to absence.
     * POST /api/admin/absences/{id}/comment
     */
    public function addComment(Request $request, int $id)
    {
        $request->validate([
            'comment' => 'required|string|min:1',
        ]);

        $absence = AbsenceNotification::find($id);

        if (!$absence) {
            return response()->json([
                'message' => 'Absence notification not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $newComment = $this->appendAuditNote(
            $absence->comment,
            $request->comment,
            $request->user()->name ?? 'System'
        );

        $absence->update([
            'comment' => $newComment,
        ]);

        Log::info('Absence comment added', [
            'absence_id' => $id,
            'comment' => $request->comment,
            'added_by' => $request->user()->id ?? null,
        ]);

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => [
                'id' => $absence->id,
                'comment' => $absence->comment,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Update absence status (excused/unexcused).
     * PATCH /api/admin/absences/{id}/status
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|string|in:PENDING,EXCUSED,UNEXCUSED',
        ]);

        $absence = AbsenceNotification::find($id);

        if (!$absence) {
            return response()->json([
                'message' => 'Absence notification not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $userId = $request->user()->id ?? null;

        $absence->update([
            'absence_status' => $request->status,
            'status_updated_by' => $userId,
            'status_updated_at' => now(),
        ]);

        // Also update the attendance follow-up if exists
        if ($absence->attendance_record_id) {
            DB::table('attendance_follow_ups')
                ->where('attendance_record_id', $absence->attendance_record_id)
                ->orderByDesc('created_at')
                ->limit(1)
                ->update([
                    'is_excused' => $request->status === AbsenceNotification::STATUS_EXCUSED,
                    'resolved' => $request->status !== AbsenceNotification::STATUS_PENDING,
                    'status' => $request->status === AbsenceNotification::STATUS_EXCUSED ? 'excused' : 'unexcused',
                    'updated_at' => now(),
                ]);
        }

        Log::info('Absence status updated', [
            'absence_id' => $id,
            'old_status' => $absence->getOriginal('absence_status'),
            'new_status' => $request->status,
            'updated_by' => $userId,
        ]);

        return response()->json([
            'message' => 'Absence status updated successfully',
            'data' => [
                'id' => $absence->id,
                'absence_status' => $absence->absence_status,
                'status_updated_at' => $absence->status_updated_at,
            ],
        ]);
    }

    /**
     * Add follow-up notes.
     * POST /api/admin/absences/{id}/follow-up
     */
    public function addFollowUp(Request $request, int $id)
    {
        $request->validate([
            'follow_up_notes' => 'required|string|min:1',
        ]);

        $absence = AbsenceNotification::find($id);

        if (!$absence) {
            return response()->json([
                'message' => 'Absence notification not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $newNotes = $this->appendAuditNote(
            $absence->follow_up_notes,
            $request->follow_up_notes,
            $request->user()->name ?? 'System'
        );

        $absence->update([
            'follow_up_notes' => $newNotes,
        ]);

        Log::info('Absence follow-up notes added', [
            'absence_id' => $id,
            'follow_up_notes' => $request->follow_up_notes,
            'added_by' => $request->user()->id ?? null,
        ]);

        return response()->json([
            'message' => 'Follow-up notes added successfully',
            'data' => [
                'id' => $absence->id,
                'follow_up_notes' => $absence->follow_up_notes,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Get absence history for a student.
     * GET /api/admin/absences/student/{studentId}/history
     */
    public function getHistory(Request $request, int $studentId)
    {
        $student = Student::find($studentId);

        if (!$student) {
            return response()->json([
                'message' => 'Student not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string|in:PENDING,EXCUSED,UNEXCUSED',
        ]);

        $query = DB::table('absence_notifications as an')
            ->leftJoin('sessions as sess', 'sess.id', '=', 'an.session_id')
            ->leftJoin('attendance_records as ar', 'ar.id', '=', 'an.attendance_record_id')
            ->where('an.student_id', $studentId)
            ->selectRaw("
                an.id,
                DATE(an.created_at) as absence_date,
                sess.name as session_name,
                sess.start_time as session_time,
                an.absence_reason,
                an.absence_status,
                an.status_updated_at,
                an.comment,
                ar.status as attendance_status
            ");

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('an.created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('an.created_at', '<=', $request->end_date);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('an.absence_status', $request->status);
        }

        $query->orderByDesc('an.created_at');

        $statsRow = DB::table('absence_notifications')
            ->where('student_id', $studentId)
            ->selectRaw(
                "COUNT(*) as total_absences,
                 SUM(CASE WHEN absence_status = ? THEN 1 ELSE 0 END) as excused,
                 SUM(CASE WHEN absence_status = ? THEN 1 ELSE 0 END) as unexcused,
                 SUM(CASE WHEN absence_status = ? THEN 1 ELSE 0 END) as pending",
                [
                    AbsenceNotification::STATUS_EXCUSED,
                    AbsenceNotification::STATUS_UNEXCUSED,
                    AbsenceNotification::STATUS_PENDING,
                ]
            )
            ->first();

        $stats = [
            'total_absences' => (int) ($statsRow->total_absences ?? 0),
            'excused' => (int) ($statsRow->excused ?? 0),
            'unexcused' => (int) ($statsRow->unexcused ?? 0),
            'pending' => (int) ($statsRow->pending ?? 0),
        ];

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->fullname,
                'code' => $student->username,
                'class' => $student->class,
            ],
            'statistics' => $stats,
            'history' => $query->paginate($request->input('per_page', 20)),
        ]);
    }

    /**
     * Get absence statistics for dashboard.
     * GET /api/admin/absences/stats
     */
    public function stats(Request $request)
    {
        try {
            $startDate = $request->input('start_date', Carbon::today()->subDays(30)->toDateString());
            $endDate = $request->input('end_date', Carbon::today()->toDateString());

            $summary = AbsenceNotification::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw(
                    "COUNT(*) as total,
                     SUM(CASE WHEN absence_status = ? THEN 1 ELSE 0 END) as pending,
                     SUM(CASE WHEN absence_status = ? THEN 1 ELSE 0 END) as excused,
                     SUM(CASE WHEN absence_status = ? THEN 1 ELSE 0 END) as unexcused",
                    [
                        AbsenceNotification::STATUS_PENDING,
                        AbsenceNotification::STATUS_EXCUSED,
                        AbsenceNotification::STATUS_UNEXCUSED,
                    ]
                )
                ->first();

            $stats = [
                'total' => (int) ($summary->total ?? 0),
                'pending' => (int) ($summary->pending ?? 0),
                'excused' => (int) ($summary->excused ?? 0),
                'unexcused' => (int) ($summary->unexcused ?? 0),
            ];

            // Get absences by class
            $byClass = DB::table('absence_notifications as an')
                ->join('students as s', 's.id', '=', 'an.student_id')
                ->leftJoin('classes as c', 'c.id', '=', 's.class_id')
                ->whereBetween('an.created_at', [$startDate, $endDate])
                ->groupBy('c.class_name', 's.class')
                ->selectRaw("
                    COALESCE(c.class_name, s.class, 'Unknown') as class_name,
                    COUNT(*) as total,
                    SUM(CASE WHEN an.absence_status = 'PENDING' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN an.absence_status = 'EXCUSED' THEN 1 ELSE 0 END) as excused,
                    SUM(CASE WHEN an.absence_status = 'UNEXCUSED' THEN 1 ELSE 0 END) as unexcused
                ")
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            // Get absences by date (last 7 days)
            $byDate = AbsenceNotification::whereBetween('created_at', [
                    Carbon::today()->subDays(6)->startOfDay(),
                    Carbon::today()->endOfDay(),
                ])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->selectRaw("
                    DATE(created_at) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN absence_status = ? THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN absence_status = ? THEN 1 ELSE 0 END) as excused,
                    SUM(CASE WHEN absence_status = ? THEN 1 ELSE 0 END) as unexcused
                ", [
                    AbsenceNotification::STATUS_PENDING,
                    AbsenceNotification::STATUS_EXCUSED,
                    AbsenceNotification::STATUS_UNEXCUSED,
                ])
                ->orderBy('date')
                ->get();

            return response()->json([
                'summary' => $stats,
                'by_class' => $byClass,
                'by_date' => $byDate,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Failed to build absence stats', [
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'error' => $exception->getMessage(),
            ]);
            return response()->json([
                'summary' => [
                    'total' => 0,
                    'pending' => 0,
                    'excused' => 0,
                    'unexcused' => 0,
                ],
                'by_class' => [],
                'by_date' => [],
                'error' => 'Unable to load absence statistics at this time.',
            ]);
        }
    }

    /**
     * Bulk update absence status.
     * POST /api/admin/absences/bulk-status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'absence_ids' => 'required|array|min:1',
            'absence_ids.*' => 'integer|exists:absence_notifications,id',
            'status' => 'required|string|in:PENDING,EXCUSED,UNEXCUSED',
        ]);

        $userId = $request->user()->id ?? null;

        $updated = AbsenceNotification::whereIn('id', $request->absence_ids)
            ->update([
                'absence_status' => $request->status,
                'status_updated_by' => $userId,
                'status_updated_at' => now(),
            ]);

        Log::info('Bulk absence status update', [
            'absence_ids' => $request->absence_ids,
            'new_status' => $request->status,
            'updated_by' => $userId,
            'count' => $updated,
        ]);

        return response()->json([
            'message' => 'Status updated successfully',
            'updated_count' => $updated,
        ]);
    }

    /**
     * Build a timestamped audit note block.
     */
    private function appendAuditNote(?string $existingText, string $message, string $userName): string
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $entry = "[{$timestamp}] {$userName}:\n{$message}";

        if (blank($existingText)) {
            return $entry;
        }

        return "{$existingText}\n\n{$entry}";
    }
}

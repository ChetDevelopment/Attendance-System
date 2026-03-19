<?php

namespace App\Http\Controllers\Student;

use App\Models\AttendanceFollowUp;
use App\Models\AttendanceRecord;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Services\SessionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class StudentDashboardController extends Controller
{
    private function hasCheckInTimeColumn(): bool
    {
        return Schema::hasColumn('attendance_records', 'check_in_time');
    }

    private function resolveRecordTimestamp(AttendanceRecord $record): ?string
    {
        $timestamp = $this->hasCheckInTimeColumn()
            ? ($record->check_in_time ?? $record->created_at)
            : $record->created_at;

        return optional($timestamp)->toIso8601String();
    }

    /**
     * GET /student/dashboard/stats
     * 
     * Get student dashboard statistics.
     */
    public function getStats(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);
        
        if (!$student) {
            return response()->json([
                'currentSession' => null,
                'todayAttendance' => null,
                'monthlyPercentage' => 0,
                'absencesCount' => 0,
                'targetPercentage' => 75,
            ], Response::HTTP_OK);
        }

        // Get current active session (time-based)
        /** @var SessionService $sessionService */
        $sessionService = app(SessionService::class);
        $currentSession = $sessionService->getCurrentSession();

        // Get today's attendance for this student
        // Use attendance_date as the canonical day field instead of created_at so
        // historical/backfilled records are picked up correctly for "today".
        $todayAttendanceQuery = AttendanceRecord::where('student_id', $student->id)
            ->with('session')
            ->whereDate('attendance_date', today())
            ->latest('attendance_date');

        if ($this->hasCheckInTimeColumn()) {
            $todayAttendanceQuery->latest('check_in_time');
        } else {
            $todayAttendanceQuery->latest('created_at');
        }

        $todayAttendance = $todayAttendanceQuery->first();

        $startOfMonth = now()->startOfMonth();
        // Aggregate monthly stats on attendance_date so records remain accurate even if inserted later.
        $monthly = AttendanceRecord::where('student_id', $student->id)
            ->whereDate('attendance_date', '>=', $startOfMonth)
            ->selectRaw(
                "COUNT(*) as total_sessions,
                 SUM(CASE WHEN LOWER(status) = 'present' THEN 1 ELSE 0 END) as present_count,
                 SUM(CASE WHEN LOWER(status) = 'late' THEN 1 ELSE 0 END) as late_count,
                 SUM(CASE WHEN LOWER(status) = 'absent' THEN 1 ELSE 0 END) as absences_count"
            )
            ->first();

        $totalSessions = (int) ($monthly->total_sessions ?? 0);
        $presentCount = (int) ($monthly->present_count ?? 0);
        $lateCount = (int) ($monthly->late_count ?? 0);
        $absencesCount = (int) ($monthly->absences_count ?? 0);
        $excusedCount = AttendanceFollowUp::query()
            ->join('attendance_records', 'attendance_records.id', '=', 'attendance_follow_ups.attendance_record_id')
            ->where('attendance_records.student_id', $student->id)
            ->whereDate('attendance_records.attendance_date', '>=', $startOfMonth)
            ->where('attendance_follow_ups.is_excused', true)
            ->distinct('attendance_follow_ups.attendance_record_id')
            ->count('attendance_follow_ups.attendance_record_id');
        $monthlyPercentage = $totalSessions > 0 ? round((($presentCount + $lateCount) / $totalSessions) * 100) : 0;

        return response()->json([
            'currentSession' => $currentSession ? [
                'id' => $currentSession->id,
                'course_name' => $currentSession->name ?? 'General Session',
                'start_time' => $currentSession->start_time,
                'end_time' => $currentSession->end_time,
                'is_active' => $currentSession->is_active ?? null,
            ] : null,
            'todayAttendance' => $todayAttendance ? [
                'id' => $todayAttendance->id,
                'course_name' => $todayAttendance->session?->name ?? 'Session',
                'status' => strtoupper($todayAttendance->status),
                'check_in_time' => $this->resolveRecordTimestamp($todayAttendance),
                'session_start' => $todayAttendance->session?->start_time,
                'session_end' => $todayAttendance->session?->end_time,
            ] : null,
            'monthlyPercentage' => $monthlyPercentage,
            'totalSessions' => $totalSessions,
            'presentCount' => $presentCount,
            'lateCount' => $lateCount,
            'absencesCount' => $absencesCount,
            'excusedCount' => $excusedCount,
            'targetPercentage' => 75,
            'student' => $student ? [
                'id' => $student->id,
                'fullname' => $student->fullname,
                'username' => $student->username,
                'email' => $student->email,
                'card_id' => $student->card_id,
                'profile' => $student->profile,
            ] : null,
        ], Response::HTTP_OK);
    }

    /**
     * GET /student/attendance/history
     * 
     * Get student attendance history.
     */
    public function getHistory(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);
        
        if (!$student) {
            return response()->json([], Response::HTTP_OK);
        }

        $limit = $request->input('limit', 50);

        $historyQuery = AttendanceRecord::with('session')
            ->where('student_id', $student->id)
            ->orderByDesc('attendance_date');

        if ($this->hasCheckInTimeColumn()) {
            $historyQuery->orderByDesc('check_in_time');
        } else {
            $historyQuery->orderByDesc('created_at');
        }

        $history = $historyQuery
            ->limit($limit)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'course_name' => $record->session?->name ?? 'Session',
                    'status' => strtoupper($record->status),
                    'check_in_time' => $this->resolveRecordTimestamp($record),
                    'session_start' => $record->session?->start_time,
                    'session_end' => $record->session?->end_time,
                ];
            });

        return response()->json($history, Response::HTTP_OK);
    }

    /**
     * POST /student/attendance/check-in
     * 
     * Handle student check-in via various methods (photo, QR code, etc.)
     */
    public function checkIn(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'sessionId' => 'required|exists:sessions,id',
            'photo' => 'nullable|string',
            'qrCode' => 'nullable|string',
            'method' => 'nullable|in:photo,qrcode,manual',
        ]);

        $validator->sometimes('photo', 'required', fn ($input) => ($input->method ?? null) === 'photo');
        $validator->sometimes('qrCode', 'required', fn ($input) => ($input->method ?? null) === 'qrcode');

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $student = $this->resolveStudentFromRequest($request);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $sessionId = $request->input('sessionId');
        $method = $request->input('method', 'manual');

        $session = Session::find($sessionId);
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Validate time window using session model helper (fallback when service config varies).
        $now = Carbon::now(config('sessions.timezone', 'Asia/Bangkok'));
        if ($session->getAttribute('is_active') === false) {
            return response()->json([
                'success' => false,
                'message' => 'Session is not active',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (method_exists($session, 'isTimeWithinSession') && !$session->isTimeWithinSession($now)) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance can only be submitted during session time',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if already checked in
        $alreadySubmitted = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->whereDate('attendance_date', now()->toDateString())
            ->exists();

        if ($alreadySubmitted) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance already recorded for this session',
                'error_code' => 'ALREADY_RECORDED',
            ], Response::HTTP_CONFLICT);
        }

        // Determine attendance status based on session time
        $isLate = method_exists($session, 'isLate') ? $session->isLate($now) : false;
        $status = $isLate ? 'Late' : 'Present';

        $submittedBy = $this->resolveUserFromRequest($request)?->id
            ?? User::where('student_id', $student->id)->value('id')
            ?? ($student->email ? User::where('email', $student->email)->value('id') : null);

        if (!$submittedBy) {
            return response()->json([
                'success' => false,
                'message' => 'Student account is not linked to a user (submitted_by missing)',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Create attendance record
        $attendance = AttendanceRecord::create([
            'student_id' => $student->id,
            'session_id' => $sessionId,
            'status' => $status,
            'location' => 'Student ' . ucfirst($method) . ' Check-in',
            'submitted_by' => $submittedBy,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in successful',
            'data' => [
                'attendance' => [
                    'id' => $attendance->id,
                    'status' => $attendance->status,
                    'session_id' => $sessionId,
                    'check_in_time' => $attendance->created_at->toIso8601String(),
                ],
            ],
        ], Response::HTTP_OK);
    }

    /**
     * POST /student/attendance/request
     * 
     * Request manual attendance correction.
     */
    public function requestManual(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'sessionId' => 'required|exists:sessions,id',
            'reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $student = $this->resolveStudentFromRequest($request);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $sessionId = $request->input('sessionId');
        $reason = $request->input('reason');

        // Check if request already exists for this session
        $attendanceRecord = AttendanceRecord::firstOrCreate(
            [
                'student_id' => $student->id,
                'session_id' => $sessionId,
                'attendance_date' => now()->toDateString(),
            ],
            [
                'status' => 'Pending',
                'submitted_by' => $this->resolveUserFromRequest($request)?->id
                    ?? User::where('student_id', $student->id)->value('id')
                    ?? ($student->email ? User::where('email', $student->email)->value('id') : null),
            ]
        );

        $existingRequest = \App\Models\AttendanceFollowUp::where('attendance_record_id', $attendanceRecord->id)
            ->where('resolved', false)
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'A request is already pending for this session',
            ], Response::HTTP_CONFLICT);
        }

        // Create follow-up request
        $followUp = \App\Models\AttendanceFollowUp::create([
            'attendance_record_id' => $attendanceRecord->id,
            'updated_by' => $this->resolveUserFromRequest($request)?->id,
            'reason' => 'student_manual_request',
            'comment' => $reason,
            'note' => $reason,
            'status' => 'pending',
            'resolved' => false,
            'is_excused' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Manual attendance request submitted successfully',
            'data' => [
                'request' => [
                    'id' => $followUp->id,
                    'session_id' => $sessionId,
                    'reason' => $reason,
                    'status' => $followUp->status,
                    'submitted_at' => $followUp->created_at->toIso8601String(),
                ],
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Resolve current student from authenticated user, session header, or fallback.
     */
    private function resolveStudentFromRequest(Request $request): ?Student
    {
        $user = $this->resolveUserFromRequest($request);
        if ($user && $user->student_id) {
            $student = Student::find($user->student_id);
            if ($student) {
                return $student;
            }
        }

        if ($user && $user->email) {
            $student = Student::where('email', $user->email)->first();
            if ($student) {
                return $student;
            }
        }

        if ($request->hasHeader('X-Student-Session')) {
            $cardId = $request->header('X-Student-Session');
            $student = Student::where('card_id', $cardId)->first();
            if ($student) {
                return $student;
            }
        }

        return null;
    }

    private function resolveUserFromRequest(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }
}

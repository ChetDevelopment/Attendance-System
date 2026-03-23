<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\AttendanceRecord;
use App\Models\Session as SessionModel;
use App\Models\SchoolClass;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Enums\AttendanceStatus;
use Illuminate\Support\Facades\Log;
use App\Services\AttendanceIntegrationService;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceIntegrationService $attendanceIntegrationService
    ) {
    }

    /**
     * Mark attendance for a student (general endpoint)
     * Payload: { class_id, student_id, status, attendance_date (optional), session_id (optional) }
     */
    public function mark(Request $request)
    {
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'student_id' => 'required|exists:students,id',
            'status' => 'required|in:present,absent,late,Present,Absent,Late',
            'attendance_date' => 'sometimes|date',
            'date' => 'sometimes|date',
            'session_id' => 'nullable|exists:sessions,id',
        ]);

        // Any teacher can mark attendance for any class
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'You are not authorized to mark attendance for this class'], 403);
        }

        $status = strtolower($request->status);

        // Map to specific handler
        switch ($status) {
            case 'present':
                return $this->markPresent($request);
            case 'absent':
                return $this->markAbsent($request);
            case 'late':
                return $this->markLate($request);
            default:
                return response()->json(['message' => 'Invalid status'], 422);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $attendances = Attendance::where('submitted_by', auth()->id())
            ->latest('date')
            ->get();

        return response()->json($attendances);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,late'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $attendance = Attendance::create([
            ...$validated,
            'submitted_by' => auth()->id(),
        ]);

        return response()->json($attendance, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance)
    {
        abort_if($attendance->submitted_by !== auth()->id(), 403, 'Unauthorized.');

        return response()->json($attendance);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        abort_if($attendance->submitted_by !== auth()->id(), 403, 'Unauthorized.');

        $validated = $request->validate([
            'date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:present,absent,late'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $attendance->update($validated);

        return response()->json($attendance);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendance)
    {
        abort_if($attendance->submitted_by !== auth()->id(), 403, 'Unauthorized.');

        $attendance->delete();

        return response()->json([
            'message' => 'Attendance deleted successfully.',
        ]);
    }

    /**
     * Admin: list attendances across teachers with optional filters.
     * Query params: class_id, session_id, date, teacher_id
     */
    public function adminIndex(Request $request)
    {
        if (auth()->user()->role->slug !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Attendance::with('submitter')->latest('date');

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('teacher_id')) {
            $query->where('submitted_by', $request->teacher_id);
        }

        $attendances = $query->get();

        return response()->json($attendances);
    }

    /**
     * Mark a single student present.
     *
     * Payload: { class_id, student_id, attendance_date (optional), session_id (optional) }
     */
    public function markPresent(Request $request)
    {
        return $this->markStudentAttendance($request, AttendanceStatus::PRESENT);
    }

    /**
     * Mark a single student absent.
     *
     * Payload: { class_id, student_id, attendance_date (optional), session_id (optional) }
     */
    public function markAbsent(Request $request)
    {
        return $this->markStudentAttendance($request, AttendanceStatus::ABSENT);
    }

    /**
     * Mark a single student late.
     *
     * Payload: { class_id, student_id, attendance_date (optional), session_id (optional), check_in_time (optional H:i) }
     */
    public function markLate(Request $request)
    {
        return $this->markStudentAttendance($request, AttendanceStatus::LATE, $request->input('check_in_time'));
    }

    /**
     * Private helper to mark student attendance.
     *
     * @param Request $request
     * @param AttendanceStatus $status
     * @param string|null $checkInTime
     * @return \Illuminate\Http\JsonResponse
     */
    private function markStudentAttendance(Request $request, AttendanceStatus $status, ?string $checkInTime = null)
    {
        // Ensure teacher role
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $rules = [
            'class_id' => 'required|exists:classes,id',
            'student_id' => 'required|exists:students,id',
            'attendance_date' => 'sometimes|date',
            'date' => 'sometimes|date',
            'session_id' => 'nullable|exists:sessions,id',
        ];

        // Add check_in_time rule for late status
        if ($status === AttendanceStatus::LATE) {
            $rules['check_in_time'] = 'nullable|date_format:H:i';
        }

        $request->validate($rules);

        // Any teacher can mark attendance for any class
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'You are not authorized to mark attendance for this class'], 403);
        }

        $date = $request->input('attendance_date') ?? $request->input('date') ?? Carbon::today()->toDateString();

        // Allowed past days
        $allowedDays = (int) env('ATTENDANCE_ALLOWED_PAST_DAYS', 0);
        try {
            $attendanceDate = Carbon::parse($date)->startOfDay();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid attendance date.'], 422);
        }

        $today = Carbon::today();
        $minDate = $today->copy()->subDays($allowedDays)->startOfDay();
        if ($attendanceDate->gt($today) || $attendanceDate->lt($minDate)) {
            return response()->json(['message' => 'Attendance date must be today or within the allowed range.'], 422);
        }

        $classId = $request->class_id;
        $studentId = $request->student_id;

        // Validate student belongs to class
        $student = Student::find($studentId);
        if (!$student || $student->class_id != $classId) {
            return response()->json(['message' => 'Student does not belong to the provided class.'], 422);
        }

        // Determine session
        $sessionId = $request->input('session_id') ?? SessionModel::where('is_active', true)->value('id');
        if (!$sessionId) {
            return response()->json(['message' => 'No active session found. Provide session_id.'], 422);
        }

        // Parse check-in time for late status
        $checkIn = null;
        if ($status === AttendanceStatus::LATE && $checkInTime) {
            try {
                $checkIn = Carbon::parse($attendanceDate->toDateString() . ' ' . $checkInTime);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Invalid check_in_time format. Use H:i.'], 422);
            }
        }

        DB::beginTransaction();
        try {
            // Find or create attendance main record
            $attendance = Attendance::where('class_id', $classId)
                ->where('session_id', $sessionId)
                ->whereDate('date', $attendanceDate->toDateString())
                ->first();

            if ($attendance) {
                if ($attendance->is_locked) {
                    return response()->json(['message' => 'Attendance for this class/session/date is locked.'], 400);
                }
            } else {
                $attendance = Attendance::create([
                    'class_id' => $classId,
                    'session_id' => $sessionId,
                    'date' => $attendanceDate->toDateString(),
                    'submitted_by' => auth()->id(),
                    'is_locked' => false,
                ]);
            }

            // Build record data
            $recordData = [
                'status' => $status->value,
                'submitted_by' => auth()->id(),
                'recorded_at' => Carbon::now(),
                'attendance_id' => $attendance->id,
            ];

            if ($checkIn) {
                $recordData['check_in_time'] = $checkIn;
            }

            // Upsert attendance record for the student
            $record = AttendanceRecord::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'session_id' => $sessionId,
                    'attendance_date' => $attendanceDate->toDateString(),
                ],
                $recordData
            );

            $this->attendanceIntegrationService->syncAttendanceRecord($record);

            DB::commit();

            $statusText = strtolower($status->value);
            return response()->json(['message' => "Student marked {$statusText}.", 'record' => $record], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Something went wrong.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Unlock an attendance (admin only) so it can be modified.
     */
    public function unlock(Attendance $attendance)
    {
        // only admin may unlock
        if (auth()->user()->role->slug !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$attendance->is_locked) {
            return response()->json(['message' => 'Attendance is already unlocked.'], 200);
        }

        $attendance->is_locked = false;
        $attendance->save();

        return response()->json(['message' => 'Attendance unlocked successfully.', 'attendance_id' => $attendance->id]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\AttendanceRecord;
use App\Models\Session as SessionModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $attendances = Attendance::where('user_id', auth()->id())
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
            'user_id' => auth()->id(),
        ]);

        return response()->json($attendance, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance)
    {
        abort_if($attendance->user_id !== auth()->id(), 403, 'Unauthorized.');

        return response()->json($attendance);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        abort_if($attendance->user_id !== auth()->id(), 403, 'Unauthorized.');

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
        abort_if($attendance->user_id !== auth()->id(), 403, 'Unauthorized.');

        $attendance->delete();

        return response()->json([
            'message' => 'Attendance deleted successfully.',
        ]);
    }

    /**
     * Mark a single student present.
     *
     * Payload: { class_id, student_id, attendance_date (optional), session_id (optional) }
     */
    public function markPresent(Request $request)
    {
        // ensure teacher role
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'student_id' => 'required|exists:students,id',
            'attendance_date' => 'sometimes|date',
            'date' => 'sometimes|date',
            'session_id' => 'nullable|exists:sessions,id',
        ]);

        $date = $request->input('attendance_date') ?? $request->input('date') ?? Carbon::today()->toDateString();

        // allowed past days
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

            // Upsert attendance record for the student
            $record = AttendanceRecord::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'session_id' => $sessionId,
                    'attendance_date' => $attendanceDate->toDateString(),
                ],
                [
                    'status' => 'present',
                    'recorded_by' => auth()->id(),
                ]
            );

            DB::commit();

            return response()->json(['message' => 'Student marked present.', 'record' => $record], 201);
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

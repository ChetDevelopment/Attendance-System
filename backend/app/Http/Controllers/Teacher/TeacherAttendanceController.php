<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TeacherAttendanceController extends Controller
{
    /**
     * Get students by class
     */
    public function getStudentsByClass($classId)
    {
        // Check teacher role
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $students = Student::where('class_id', $classId)
            ->select('id', 'first_name', 'last_name')
            ->get();

        return response()->json([
            'class_id' => $classId,
            'students' => $students
        ]);
    }

    /**
     * Submit Manual Attendance
     */
    public function submitAttendance(Request $request)
    {
        // Validate teacher role
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $rules = [
            'class_id' => 'required|exists:classes,id',
            'session_id' => 'nullable|exists:sessions,id',
            'date' => 'sometimes|date',
            'attendance_date' => 'sometimes|date',
        ];

        if ($request->has('records')) {
            $rules['records'] = 'required|array';
            $rules['records.*.student_id'] = 'required|exists:students,id';
            $rules['records.*.status'] = 'required|in:present,absent,late';
        } else {
            $rules['students'] = 'required|array';
            $rules['students.*.student_id'] = 'required|exists:students,id';
            $rules['students.*.status'] = 'required|in:present,absent,late';
        }

        $request->validate($rules);

        // Normalize inputs to use a common variable set
        $date = $request->input('attendance_date') ?? $request->input('date');

        if (!$date) {
            return response()->json(['message' => 'The date (attendance_date or date) is required.'], 422);
        }

        $students = $request->input('records') ?? $request->input('students');

        // Determine session: prefer provided session_id, otherwise pick an active session
        $sessionId = $request->input('session_id');

        if (!$sessionId) {
            $sessionId = Session::where('is_active', true)->value('id');
        }

        if (!$sessionId) {
            return response()->json([
                'message' => 'No active session found. Please provide a session_id.'
            ], 422);
        }

        // Validate attendance date is today or within allowed window
        $allowedDays = (int) env('ATTENDANCE_ALLOWED_PAST_DAYS', 0);
        try {
            $attendanceDate = Carbon::parse($date)->startOfDay();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid attendance date.'], 422);
        }

        $today = Carbon::today();
        $minDate = $today->copy()->subDays($allowedDays)->startOfDay();

        if ($attendanceDate->gt($today) || $attendanceDate->lt($minDate)) {
            return response()->json([
                'message' => 'Attendance date must be today or within the allowed range.'
            ], 422);
        }

        // Check existing attendance for this class, session and date
        $existing = Attendance::where('class_id', $request->class_id)
            ->where('session_id', $sessionId)
            ->whereDate('date', $attendanceDate->toDateString())
            ->first();

        if ($existing) {
            if ($existing->is_locked) {
                return response()->json([
                    'message' => 'Attendance for this class/session/date is locked and cannot be modified.'
                ], 400);
            }

            return response()->json([
                'message' => 'Attendance already submitted for this class and session on this date.'
            ], 400);
        }

        Log::info('TeacherAttendance submit', [
            'input' => $request->all(),
            'sessionId' => $sessionId,
            'date' => $date,
            'students_count' => is_array($students) ? count($students) : null,
        ]);

        DB::beginTransaction();

        try {

            // Create attendance (main record)
            $attendance = Attendance::create([
                'class_id' => $request->class_id,
                'session_id' => $sessionId,
                'date' => $date,
                'submitted_by' => auth()->id(),
                'is_locked' => true
            ]);

            // Insert student attendance records
            foreach ($students as $student) {
                $studentId = $student['student_id'] ?? ($student['id'] ?? null);

                if (!$studentId) {
                    continue;
                }

                AttendanceRecord::create([
                    'student_id' => $studentId,
                    'session_id' => $sessionId,
                    'submitted_by' => auth()->id(),
                    'attendance_date' => $date,
                    'status' => $student['status'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Attendance submitted successfully.',
                'attendance_id' => $attendance->id
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

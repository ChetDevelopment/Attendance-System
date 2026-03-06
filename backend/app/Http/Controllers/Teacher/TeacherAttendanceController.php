<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\Session;
use Illuminate\Support\Facades\DB;

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

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'session_id' => 'required|exists:sessions,id',
            'date' => 'required|date',
            'students' => 'required|array',
            'students.*.student_id' => 'required|exists:students,id',
            'students.*.status' => 'required|in:present,absent,late',
        ]);

        // Prevent duplicate attendance
        $exists = Attendance::where('class_id', $request->class_id)
            ->where('session_id', $request->session_id)
            ->where('date', $request->date)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Attendance already submitted for this class and session.'
            ], 400);
        }

        DB::beginTransaction();

        try {

            // Create attendance (main record)
            $attendance = Attendance::create([
                'class_id' => $request->class_id,
                'session_id' => $request->session_id,
                'date' => $request->date,
                'submitted_by' => auth()->id(),
                'is_locked' => true
            ]);

            // Insert student attendance records
            foreach ($request->students as $student) {
                AttendanceRecord::create([
                    'attendance_id' => $attendance->id,
                    'student_id' => $student['student_id'],
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
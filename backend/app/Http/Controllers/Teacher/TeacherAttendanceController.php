<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\Session;
use App\Models\SchoolClass;
use App\Models\AbsenceComment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Enums\AttendanceStatus;
use App\Models\Student as StudentModel;
use App\Services\ActivityLogger;

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

        $class = SchoolClass::find($classId);
        $className = $class ? $class->name : null;

        $students = Student::where('class_id', $classId)
            ->select('id', 'first_name', 'last_name', 'student_code', 'class_id', 'face_image')
            ->get()
            ->map(function ($student) use ($className) {
                // Build photo URL
                $photoUrl = null;
                if ($student->face_image) {
                    // Check if it's a full URL or just a path
                    if (str_starts_with($student->face_image, 'http')) {
                        $photoUrl = $student->face_image;
                    } else {
                        // It's a path, prepend the frontend URL
                        $photoUrl = config('app.frontend_url', 'http://localhost:5173') . '/' . $student->face_image;
                    }
                }

                return [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'student_code' => $student->student_code,
                    'class_id' => $student->class_id,
                    'class' => $className,
                    'class_name' => $className,
                    'photo' => $photoUrl,
                ];
            });

        return response()->json([
            'class_id' => $classId,
            'class_name' => $className,
            'class' => $className,
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

        // Get the class (no teacher authorization check - teachers can mark attendance for any class)
        $class = SchoolClass::find($request->class_id);

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

                // Validate the student belongs to the provided class
                $s = StudentModel::find($studentId);
                if (!$s || $s->class_id != $request->class_id) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Student {$studentId} does not belong to class {$request->class_id}."
                    ], 422);
                }

                // Normalize status to enum value and save recorded_at
                try {
                    $status = AttendanceStatus::fromString($student['status'])->value;
                } catch (\InvalidArgumentException $e) {
                    DB::rollBack();
                    return response()->json(['message' => $e->getMessage()], 422);
                }

                AttendanceRecord::create([
                    'student_id' => $studentId,
                    'session_id' => $sessionId,
                    'recorded_by' => auth()->id(),
                    'attendance_date' => $date,
                    'status' => $status,
                    'recorded_at' => Carbon::now(),
                ]);
            }

            DB::commit();

            // Log teacher activity (CREATE)
            ActivityLogger::log(
                auth()->id(),
                'CREATE',
                "Submitted attendance for class {$request->class_id}, session {$sessionId}, date {$date}",
                request()->ip()
            );

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

    /**
     * Get all students (for teachers)
     */
    public function getAllStudents()
    {
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $students = Student::select('id', 'first_name', 'last_name', 'student_code', 'class_id', 'face_image', 'contact', 'parent_number')
            ->with('class:id,name,code')
            ->get()
            ->map(function ($student) {
                // Build photo URL
                $photoUrl = null;
                if ($student->face_image) {
                    if (str_starts_with($student->face_image, 'http')) {
                        $photoUrl = $student->face_image;
                    } else {
                        $photoUrl = config('app.frontend_url', 'http://localhost:5173') . '/' . $student->face_image;
                    }
                }

                return [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'student_code' => $student->student_code,
                    'class_id' => $student->class_id,
                    'class' => $student->class->name ?? null,
                    'class_name' => $student->class->name ?? null,
                    'avatar' => $photoUrl,
                    'contact' => $student->contact ?? $student->parent_number,
                ];
            });

        return response()->json($students);
    }

    /**
     * Get teacher schedule/sessions
     */
    public function getSchedule()
    {
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacherId = auth()->id();

        // Get all active sessions
        $sessions = Session::where('is_active', true)
            ->orderBy('start_time')
            ->get();

        // Get all active classes (for schools where one teacher can teach all classes)
        $classes = SchoolClass::where('is_active', true)
            ->select('id', 'name', 'code')
            ->get();

        // Get all teachers for the dropdown
        $teachers = \App\Models\User::whereHas('role', function ($query) {
            $query->where('slug', 'teacher');
        })
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->get()
            ->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                ];
            });

        // Return the teacher's classes (or first class as default)
        return response()->json([
            'sessions' => $sessions,
            'classes' => $classes,
            'teachers' => $teachers,
            'class_id' => $classes->first()->id ?? null,
        ]);
    }

    /**
     * Get teacher dashboard data
     */
    public function getDashboard()
    {
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacherId = auth()->id();
        $today = Carbon::today();

        // Get today's attendance count
        $todayAttendance = Attendance::whereDate('date', $today)
            ->where('submitted_by', $teacherId)
            ->count();

        // Get total classes handled by this teacher
        $totalClasses = SchoolClass::where('is_active', true)->count();

        // Get recent attendance records (last 7 days)
        $recentAttendances = Attendance::where('submitted_by', $teacherId)
            ->whereDate('date', '>=', $today->subDays(7))
            ->with('class:id,name')
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        // Get pending justifications (absent students with pending status)
        $pendingJustifications = AttendanceRecord::whereHas('attendance', function ($query) use ($teacherId) {
            $query->where('submitted_by', $teacherId);
        })
            ->where('status', AttendanceStatus::ABSENT)
            ->whereNull('justification_status')
            ->count();

        return response()->json([
            'today_attendance' => $todayAttendance,
            'total_classes' => $totalClasses,
            'pending_justifications' => $pendingJustifications,
            'recent_attendances' => $recentAttendances,
        ]);
    }

    /**
     * Get justifications/absence requests
     */
    public function getJustifications()
    {
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacherId = auth()->id();

        // Get absent records that might need justification
        $absentRecords = AttendanceRecord::whereHas('attendance', function ($query) use ($teacherId) {
            $query->where('submitted_by', $teacherId);
        })
            ->where('status', AttendanceStatus::ABSENT)
            ->with([
                'student:id,first_name,last_name,student_code,class_id,face_image',
                'session:id,name',
                'attendance.class:id,name,code'
            ])
            ->orderBy('attendance_date', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'studentId' => $record->student->student_code,
                    'studentName' => $record->student->first_name . ' ' . $record->student->last_name,
                    'student_code' => $record->student->student_code,
                    'studentPhoto' => $record->student->face_image ?? null,
                    'classCode' => $record->attendance->class->code ?? null,
                    'subject' => $record->attendance->class->name ?? null,
                    'sessionName' => $record->session->name ?? null,
                    'date' => $record->attendance_date,
                    'status' => $record->status,
                    'justificationStatus' => $record->justification_status,
                    'educationComment' => $record->comment,
                ];
            });

        return response()->json($absentRecords);
    }

    /**
     * Get attendance history
     */
    public function getHistory()
    {
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacherId = auth()->id();

        // Get attendance history with class and session info
        $history = Attendance::where('submitted_by', $teacherId)
            ->with([
                'class:id,name,code',
                'session:id,name,start_time,end_time',
                'records'
            ])
            ->orderBy('date', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($attendance) {
                // Calculate attendance rate
                $totalRecords = $attendance->records->count();
                $presentCount = $attendance->records->where('status', 'PRESENT')->count();
                $attendanceRate = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100) : 0;

                return [
                    'id' => $attendance->id,
                    'date' => $attendance->date,
                    'class_id' => $attendance->class_id,
                    'subject' => $attendance->class->name ?? null,
                    'classCode' => $attendance->class->code ?? null,
                    'session_id' => $attendance->session_id,
                    'session_name' => $attendance->session->name ?? null,
                    'session_time' => ($attendance->session->start_time ?? '') . ' - ' . ($attendance->session->end_time ?? ''),
                    'is_locked' => $attendance->is_locked,
                    'record_count' => $totalRecords,
                    'attendanceRate' => $attendanceRate,
                    'presentCount' => $presentCount,
                    'totalStudents' => $totalRecords,
                ];
            });

        return response()->json($history);
    }

    /**
     * Get teacher notifications
     */
    public function getNotifications()
    {
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacherId = auth()->id();
        $today = Carbon::today();

        // Build notifications array
        $notifications = [];

        // Check for pending justifications
        $pendingJustifications = AttendanceRecord::whereHas('attendance', function ($query) use ($teacherId) {
            $query->where('submitted_by', $teacherId);
        })
            ->where('status', AttendanceStatus::ABSENT)
            ->whereNull('justification_status')
            ->count();

        if ($pendingJustifications > 0) {
            $notifications[] = [
                'id' => 'pending_justifications',
                'type' => 'warning',
                'title' => 'Pending Justifications',
                'message' => "You have {$pendingJustifications} absent student(s) awaiting justification.",
                'created_at' => $today->toISOString(),
            ];
        }

        // Check for today's attendance not submitted
        $todayAttendance = Attendance::whereDate('date', $today)
            ->where('submitted_by', $teacherId)
            ->exists();

        if (!$todayAttendance) {
            $notifications[] = [
                'id' => 'attendance_not_submitted',
                'type' => 'info',
                'title' => 'Attendance Not Submitted',
                'message' => 'You have not submitted attendance for today.',
                'created_at' => $today->toISOString(),
            ];
        }

        return response()->json($notifications);
    }
}

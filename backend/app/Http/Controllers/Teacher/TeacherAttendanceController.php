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
use App\Models\AcademicYear;
use App\Models\AbsenceNotification;
use App\Services\TimetableService;
use App\Services\AttendanceIntegrationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Enums\AttendanceStatus;
use App\Models\Student as StudentModel;
use App\Services\ActivityLogger;

class TeacherAttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceIntegrationService $attendanceIntegrationService
    ) {
    }

    private function isTeacher(): bool
    {
        $roleName = strtolower((string) optional(auth()->user()?->role)->name);

        return $roleName === 'teacher';
    }

    /**
     * Get students by class
     */
    public function getStudentsByClass($classId)
    {
        // Check teacher role
        if (!$this->isTeacher()) {
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
        if (!$this->isTeacher()) {
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
            $rules['records.*.status'] = 'required|in:present,absent,late,Present,Absent,Late';
        } else {
            $rules['students'] = 'required|array';
            $rules['students.*.student_id'] = 'required|exists:students,id';
            $rules['students.*.status'] = 'required|in:present,absent,late,Present,Absent,Late';
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

                $record = AttendanceRecord::create([
                    'student_id' => $studentId,
                    'session_id' => $sessionId,
                    'attendance_id' => $attendance->id,
                    'submitted_by' => auth()->id(),
                    'attendance_date' => $date,
                    'status' => $status,
                    'recorded_at' => Carbon::now(),
                ]);

                $this->attendanceIntegrationService->syncAttendanceRecord($record);
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
    public function getAllStudents(Request $request)
    {
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $academicYearId = $request->input('academic_year_id');

        $students = Student::select('id', 'first_name', 'last_name', 'student_code', 'class_id', 'face_image', 'contact', 'parent_number')
            ->with('class:id,name,code,academic_year_id')
            ->when($academicYearId, function ($query) use ($academicYearId) {
                $query->whereHas('class', function ($classQuery) use ($academicYearId) {
                    $classQuery->where('academic_year_id', $academicYearId);
                });
            })
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
    public function getSchedule(Request $request)
    {
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacherId = auth()->id();
        $academicYearId = $request->input('academic_year_id');

        // Get all active sessions
        $sessions = Session::where('is_active', true)
            ->orderBy('start_time')
            ->get();

        // Get classes filtered by academic year
        $classQuery = SchoolClass::where('is_active', true)
            ->select('id', 'name', 'code', 'academic_year_id');

        if ($academicYearId) {
            $classQuery->where('academic_year_id', $academicYearId);
        } else {
            // When no academic year is selected, default to the current active academic year
            // to avoid showing duplicate classes from different years
            $currentAcademicYear = AcademicYear::where('is_active', true)->first();
            if ($currentAcademicYear) {
                $classQuery->where('academic_year_id', $currentAcademicYear->id);
            }
        }

        $classes = $this->deduplicateClassesForTeacher(
            $classQuery->orderBy('name')->orderBy('id')->get()
        );

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
     * Hide legacy duplicate classes for the same academic year.
     *
     * Some data sets contain both a short code like `WEP-A-20261` and a longer
     * generated code like `WEP-A-20252026` for the same class/year. We keep the
     * shorter code in teacher attendance to avoid duplicate dropdown options.
     */
    private function deduplicateClassesForTeacher($classes)
    {
        return $classes
            ->groupBy(fn ($class) => ($class->academic_year_id ?? 'null') . ':' . trim((string) $class->name))
            ->map(function ($group) {
                return $group
                    ->sort(function ($left, $right) {
                        $lengthCompare = strlen((string) $left->code) <=> strlen((string) $right->code);

                        if ($lengthCompare !== 0) {
                            return $lengthCompare;
                        }

                        return $left->id <=> $right->id;
                    })
                    ->first();
            })
            ->sortBy([
                ['name', 'asc'],
                ['code', 'asc'],
            ])
            ->values();
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
        $now = Carbon::now();

        // Get all active classes (any teacher can teach any class)
        $teacherClasses = SchoolClass::where('is_active', true)
            ->select('id', 'name', 'code')
            ->get();

        // Format today's classes with session info
        $todayClasses = $teacherClasses->map(function ($cls) {
            return [
                'id' => $cls->id,
                'subject' => $cls->name,
                'classCode' => $cls->code,
                'start_time' => null, // Sessions are not directly linked to classes in current schema
                'end_time' => null,
            ];
        });

        // Get active session (currently happening based on current time)
        $currentTime = $now->format('H:i:s');
        $activeSession = Session::where('is_active', true)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->orderBy('start_time')
            ->first();

        // If no session is currently happening, get the next upcoming session
        if (!$activeSession) {
            $activeSession = Session::where('is_active', true)
                ->where('start_time', '>', $currentTime)
                ->orderBy('start_time')
                ->first();
        }

        // Get today's attendance counts - show ALL records for today (not filtered by teacher)
        $todayRecordsQuery = AttendanceRecord::query()->where(function ($query) use ($today) {
            $query->whereDate('attendance_date', $today)
                ->orWhere(function ($fallback) use ($today) {
                    $fallback->whereNull('attendance_date')
                        ->whereDate('date', $today);
                });
        });

        // Treat late students as checked in on the dashboard.
        $checkedInCount = (clone $todayRecordsQuery)
            ->whereRaw('UPPER(status) IN (?, ?)', ['PRESENT', 'LATE'])
            ->count();
        $absentCount = (clone $todayRecordsQuery)
            ->whereRaw('UPPER(status) = ?', ['ABSENT'])
            ->count();
        $totalRecordsToday = (clone $todayRecordsQuery)->count();

        // Get next upcoming session (after current active session ends)
        $nextToday = null;
        if ($activeSession) {
            $nextSession = Session::where('is_active', true)
                ->where('start_time', '>', $activeSession->end_time)
                ->orderBy('start_time')
                ->first();

            if ($nextSession) {
                $nextToday = [
                    'id' => $nextSession->id,
                    'subject' => $nextSession->name,
                    'start_time' => $nextSession->start_time,
                    'end_time' => $nextSession->end_time,
                ];
            }
        } else {
            // No active session - show next upcoming session
            $nextSession = Session::where('is_active', true)
                ->where('start_time', '>', $currentTime)
                ->orderBy('start_time')
                ->first();

            if ($nextSession) {
                $nextToday = [
                    'id' => $nextSession->id,
                    'subject' => $nextSession->name,
                    'start_time' => $nextSession->start_time,
                    'end_time' => $nextSession->end_time,
                ];
            }
        }

        return response()->json([
            'today_classes' => $todayClasses,
            'active' => $activeSession ? [
                'id' => $activeSession->id,
                'subject' => $activeSession->name,
                'start_time' => $activeSession->start_time,
                'end_time' => $activeSession->end_time,
            ] : null,
            'next_today' => $nextToday,
            'checked_in_count' => $checkedInCount,
            'absent_count' => $absentCount,
            'debug' => [
                'today' => $today->toDateString(),
                'teacher_id' => $teacherId,
                'total_records_today' => $totalRecordsToday,
            ]
        ]);
    }

    /**
     * Get today's schedule from external timetable API
     * This fetches the teacher's schedule from https://timetables2.pnc.passerellesnumeriques.org/
     */
    public function getTodaySchedule(Request $request)
    {
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacher = auth()->user();
        $date = $request->input('date', Carbon::today()->toDateString());

        // Get teacher's calendar ID from user profile first, then fall back to config/mapping
        $calendarId = null;

        // Check if teacher has a calendar_id in their profile
        if (!empty($teacher->calendar_id)) {
            $calendarId = $teacher->calendar_id;
        }

        // If no calendar_id in profile, try to get it from the TimetableService mapping by teacher name
        if (empty($calendarId)) {
            $timetableService = new TimetableService();
            $calendarId = $timetableService->getCalendarIdByTeacherName($teacher->name);
        }

        // Fall back to default calendar ID from config
        if (empty($calendarId)) {
            $calendarId = config(
                'app.teacher_calendar_id',
                'c_1886h9lqonri4ig0noe2vrfvp8fb8@resource.calendar.google.com'
            );
        }

        // Allow overriding calendar ID via request (for testing)
        if ($request->has('calendar_id')) {
            $calendarId = $request->input('calendar_id');
        }

        // DEBUG: Log the calendar ID being used
        Log::info('getTodaySchedule: Using calendar_id: ' . $calendarId . ' for date: ' . $date);

        try {
            $timetableService = new TimetableService();
            $scheduleData = $timetableService->getTeacherSchedule($calendarId, $date);

            // DEBUG: Log the result
            Log::info('getTodaySchedule: Result - total_sessions: ' . ($scheduleData['total_sessions'] ?? 0));

            return response()->json([
                'success' => true,
                'date' => $scheduleData['date'],
                'sessions' => $scheduleData['sessions'],
                'total_sessions' => $scheduleData['total_sessions'],
                'teacher_name' => $teacher->name,
                'calendar_id' => $calendarId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch teacher schedule: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch schedule from timetable API',
                'sessions' => [],
                'total_sessions' => 0,
            ], 500);
        }
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

        // Get absent and late records that might need justification
        $absentRecords = DB::table('attendance_records as ar')
            ->join('students as s', 's.id', '=', 'ar.student_id')
            ->join('sessions as sess', 'sess.id', '=', 'ar.session_id')
            ->leftJoin('classes as c', 'c.id', '=', 's.class_id')
            ->join('attendances as a', function ($join) {
                $join->on('a.class_id', '=', 's.class_id')
                    ->on('a.session_id', '=', 'ar.session_id')
                    ->whereRaw('DATE(a.date) = DATE(COALESCE(ar.attendance_date, ar.date))');
            })
            ->leftJoin('absence_notifications as an', function ($join) {
                $join->on('an.attendance_record_id', '=', 'ar.id')
                    ->where('an.status', '=', 'active');
            })
            ->where('a.submitted_by', $teacherId)
            ->whereRaw('UPPER(ar.status) IN (?, ?)', ['ABSENT', 'LATE'])
            ->selectRaw("
                ar.id,
                s.student_code,
                s.face_image,
                COALESCE(NULLIF(s.fullname, ''), TRIM(CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')))) as student_name,
                COALESCE(c.code, s.class, '') as class_code,
                COALESCE(c.name, s.class, 'Unknown Class') as class_name,
                sess.name as session_name,
                COALESCE(ar.attendance_date, ar.date) as attendance_date,
                ar.status,
                an.absence_status,
                COALESCE(an.comment, an.follow_up_notes, ar.justification, '') as education_comment
            ")
            ->orderByDesc(DB::raw('COALESCE(ar.attendance_date, ar.date)'))
            ->limit(50)
            ->get()
            ->map(function ($record) {
                // Build photo URL
                $photoUrl = null;
                if ($record->face_image) {
                    if (str_starts_with($record->face_image, 'http')) {
                        $photoUrl = $record->face_image;
                    } else {
                        $photoUrl = config('app.frontend_url', 'http://localhost:5173') . '/' . $record->face_image;
                    }
                }

                return [
                    'id' => $record->id,
                    'studentId' => $record->student_code,
                    'studentName' => $record->student_name,
                    'student_code' => $record->student_code,
                    'studentPhoto' => $photoUrl,
                    'classCode' => $record->class_code,
                    'subject' => $record->class_name,
                    'sessionName' => $record->session_name,
                    'date' => $record->attendance_date,
                    'status' => $record->status,
                    'justificationStatus' => $record->absence_status,
                    'educationComment' => $record->education_comment,
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

        // Get teacher's assigned classes
        $teacherClassIds = SchoolClass::where('teacher_id', $teacherId)
            ->where('is_active', true)
            ->pluck('id');

        // Check for pending justifications - students in teacher's classes who are absent without justification
        $pendingJustifications = 0;
        if ($teacherClassIds->isNotEmpty()) {
            $pendingJustifications = DB::table('attendance_records as ar')
                ->join('students as s', 's.id', '=', 'ar.student_id')
                ->leftJoin('absence_notifications as an', function ($join) {
                    $join->on('an.attendance_record_id', '=', 'ar.id')
                        ->where('an.status', '=', 'active');
                })
                ->whereIn('s.class_id', $teacherClassIds)
                ->whereDate(DB::raw('COALESCE(ar.attendance_date, ar.date)'), $today)
                ->whereRaw('UPPER(ar.status) = ?', ['ABSENT'])
                ->where(function ($query) {
                    $query->whereNull('an.id')
                        ->orWhere('an.absence_status', AbsenceNotification::STATUS_PENDING);
                })
                ->count();
        }

        if ($pendingJustifications > 0) {
            $notifications[] = [
                'id' => 'pending_justifications_' . $today->format('Y-m-d'),
                'unread' => true,
                'type' => 'warning',
                'title' => 'Pending Justifications',
                'message' => "You have {$pendingJustifications} absent student(s) awaiting justification.",
                'time' => $today->format('h:i A'),
                'created_at' => $today->toISOString(),
            ];
        }

        // Check for today's attendance not submitted for any of teacher's classes
        if ($teacherClassIds->isNotEmpty()) {
            $submittedClasses = Attendance::whereDate('date', $today)
                ->whereIn('class_id', $teacherClassIds)
                ->pluck('class_id')
                ->toArray();

            $unsubmittedClasses = $teacherClassIds->filter(function ($classId) use ($submittedClasses) {
                return !in_array($classId, $submittedClasses);
            });

            if ($unsubmittedClasses->isNotEmpty()) {
                $classCount = $unsubmittedClasses->count();
                $notifications[] = [
                    'id' => 'attendance_not_submitted_' . $today->format('Y-m-d'),
                    'unread' => true,
                    'type' => 'info',
                    'title' => 'Attendance Not Submitted',
                    'message' => "You have not submitted attendance for {$classCount} class(es) today.",
                    'time' => $today->format('h:i A'),
                    'created_at' => $today->toISOString(),
                ];
            }
        }

        // Add a success notification if all attendance is submitted and no pending justifications
        if (empty($notifications)) {
            $notifications[] = [
                'id' => 'all_caught_up_' . $today->format('Y-m-d'),
                'unread' => false,
                'type' => 'success',
                'title' => 'All Caught Up',
                'message' => 'You have no pending tasks. Great job!',
                'time' => $today->format('h:i A'),
                'created_at' => $today->toISOString(),
            ];
        }

        return response()->json($notifications);
    }

    /**
     * Get all academic years
     */
    public function getAcademicYears()
    {
        if (auth()->user()->role->slug !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return response()->json([
            'academic_years' => $academicYears,
        ]);
    }
}

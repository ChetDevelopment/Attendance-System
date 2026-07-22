<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbsenceNotification;
use App\Models\AttendanceFollowUp;
use App\Models\AttendanceRecord;
use App\Models\Student;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EducationDashboardController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService)
    {
    }

    private function buildStudentPhotoUrl($student): ?string
    {
        $photo = $student?->profile ?: $student?->face_image;

        if (!$photo) {
            return null;
        }

        if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
            return $photo;
        }

        if (str_starts_with($photo, '/storage') || str_starts_with($photo, 'storage/')) {
            return rtrim(config('app.url', 'http://localhost'), '/') . '/' . ltrim($photo, '/');
        }

        return rtrim(config('app.frontend_url', 'http://localhost:3000'), '/') . '/' . ltrim($photo, '/');
    }

    public function classAttendanceReport(Request $request)
    {
        $period = $request->input('period', 'today');
        $academicYearId = $request->input('academic_year_id');
        $classId = $request->input('class_id');

        $startDate = match ($period) {
            'weekly' => now()->subDays(7)->toDateString(),
            'monthly' => now()->subDays(30)->toDateString(),
            default => now()->toDateString(),
        };

        $codeColumn = Schema::hasColumn('students', 'username') ? 's.username' : 's.student_code';
        $profileColumn = Schema::hasColumn('students', 'profile') ? 's.profile' : 'NULL';
        $classNameColumn = Schema::hasColumn('classes', 'class_name') ? 'c.class_name' : 'c.name';

        $query = DB::table('attendance_records as ar')
            ->join('students as s', 's.id', '=', 'ar.student_id')
            ->leftJoin('classes as c', 'c.id', '=', 's.class_id')
            ->where(function ($q) use ($startDate, $period) {
                if ($period === 'today') {
                    $q->whereDate('ar.attendance_date', now()->toDateString())
                        ->orWhere(function ($fallback) {
                            $fallback->whereNull('ar.attendance_date')
                                ->whereDate('ar.date', now()->toDateString());
                        });
                } else {
                    $q->whereDate('ar.attendance_date', '>=', $startDate)
                        ->orWhere(function ($fallback) use ($startDate) {
                            $fallback->whereNull('ar.attendance_date')
                                ->whereDate('ar.date', '>=', $startDate);
                        });
                }
            });

        if ($classId) {
            $query->where('s.class_id', $classId);
        }

        if ($academicYearId) {
            $query->where('s.academic_year_id', $academicYearId);
        }

        $selectRaw = "
            s.id as student_id,
            COALESCE(s.fullname, CONCAT(s.first_name, ' ', s.last_name)) as name,
            {$codeColumn} as code,
            {$profileColumn} as photo,
            {$classNameColumn} as class_name,
            SUM(CASE WHEN LOWER(ar.status) = 'late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN LOWER(ar.status) = 'absent' THEN 1 ELSE 0 END) as absent_count
        ";

        $groupBy = ['s.id', 's.fullname', 's.first_name', 's.last_name'];
        if (Schema::hasColumn('students', 'username')) {
            $groupBy[] = 's.username';
        }
        if (Schema::hasColumn('students', 'profile')) {
            $groupBy[] = 's.profile';
        }
        if (Schema::hasColumn('classes', 'class_name')) {
            $groupBy[] = 'c.class_name';
        } else {
            $groupBy[] = 'c.name';
        }

        $students = $query
            ->selectRaw($selectRaw)
            ->groupBy(...$groupBy)
            ->orderBy('name')
            ->get()
            ->map(function ($student, $index) {
                return [
                    'no' => $index + 1,
                    'name' => $student->name,
                    'code' => $student->code ?? '',
                    'photo' => $this->buildStudentPhotoUrl((object) ['profile' => $student->photo ?? null]),
                    'late_count' => (int) ($student->late_count ?? 0),
                    'absent_count' => (int) ($student->absent_count ?? 0),
                ];
            });

        return response()->json($students);
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
                'student:id,fullname,class,class_id,profile,face_image',
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
                    'studentPhoto' => $this->buildStudentPhotoUrl($absence->student),
                    'date' => $attendanceDate,
                    'resolved' => strtoupper((string) $absence->absence_status) !== 'PENDING',
                ];
            });

        return response()->json($rows);
    }

    public function allAbsent()
    {
        $rows = AbsenceNotification::query()
            ->with('student:id,fullname,class,class_id,profile,face_image')
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
                    'studentPhoto' => $this->buildStudentPhotoUrl($absence->student),
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

    public function classReports(Request $request)
    {
        $period = $request->input('period', 'today');
        $academicYearId = $request->input('academic_year_id');
        $classId = $request->input('class_id');
        
        $startDate = match($period) {
            'weekly' => now()->subDays(7)->toDateString(),
            'monthly' => now()->subDays(30)->toDateString(),
            default => now()->toDateString(),
        };
        
        $query = DB::table('attendance_records as ar')
            ->join('students as s', 's.id', '=', 'ar.student_id')
            ->leftJoin('classes as c', 'c.id', '=', 's.class_id')
            ->where(function ($q) use ($startDate, $period) {
                if ($period === 'today') {
                    $q->whereDate('ar.attendance_date', now()->toDateString())
                        ->orWhere(function ($fallback) {
                            $fallback->whereNull('ar.attendance_date')
                                ->whereDate('ar.date', now()->toDateString());
                        });
                } else {
                    $q->whereDate('ar.attendance_date', '>=', $startDate)
                        ->orWhere(function ($fallback) use ($startDate) {
                            $fallback->whereNull('ar.attendance_date')
                                ->whereDate('ar.date', '>=', $startDate);
                        });
                }
            });
        
        if ($classId) {
            $query->where('s.class_id', $classId);
        }
        
        if ($academicYearId) {
            $query->where('s.academic_year_id', $academicYearId);
        }
        
        $classColumn = Schema::hasColumn('classes', 'class_name') ? 'c.class_name' : 'c.name';

        $rows = $query
            ->selectRaw("
                COALESCE({$classColumn}, 'Unknown Class') as class,
                SUM(CASE WHEN LOWER(ar.status) = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN LOWER(ar.status) = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN LOWER(ar.status) = 'late' THEN 1 ELSE 0 END) as late_count
            ")
            ->groupByRaw("COALESCE({$classColumn}, 'Unknown Class')")
            ->orderByRaw("COALESCE({$classColumn}, 'Unknown Class')")
            ->get()
            ->map(function ($row) {
                $total = (int) $row->present_count + (int) $row->absent_count + (int) $row->late_count;
                return [
                    'class' => $row->class,
                    'present_count' => (int) $row->present_count,
                    'absent_count' => (int) $row->absent_count,
                    'late_count' => (int) $row->late_count,
                    'total' => $total,
                ];
            });
        
        return response()->json($rows);
    }

    public function reportAcademicYears()
    {
        $years = DB::table('academic_years')
            ->select('id', 'name')
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(fn($y) => ['id' => (string) $y->id, 'name' => $y->name]);
        
        return response()->json($years);
    }

    public function reportClasses()
    {
        $classes = DB::table('classes')
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => ['id' => (string) $c->id, 'name' => $c->name]);
        
        return response()->json($classes);
    }

    public function reportStudents(Request $request)
    {
        $classId = $request->input('class_id');
        $academicYearId = $request->input('academic_year_id');
        
        $query = Student::query()
            ->with('schoolClass:id,name,class_name');
        
        if ($classId) {
            $query->where('class_id', $classId);
        }
        
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        
        $students = $query
            ->select('id', 'fullname', 'first_name', 'last_name', 'username', 'class_id')
            ->orderBy('fullname')
            ->limit(100)
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->fullname ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                    'code' => $s->username,
                    'class' => $s->schoolClass?->class_name ?? $s->schoolClass?->name ?? 'Unknown',
                ];
            });
        
        return response()->json($students);
    }

    public function exportClassReports()
    {
        $studentClassColumn = Schema::hasColumn('students', 'class') ? 's.class' : 'NULL';
        $classNameColumn = Schema::hasColumn('classes', 'class_name') ? 'c.class_name' : 'c.name';
        $classLabel = "COALESCE({$classNameColumn}, {$studentClassColumn}, 'Unknown Class')";

        // Get all attendance data grouped by class
        $classSummary = DB::table('attendance_records as ar')
            ->join('students as s', 's.id', '=', 'ar.student_id')
            ->leftJoin('classes as c', 'c.id', '=', 's.class_id')
            ->selectRaw("
                {$classLabel} as class,
                SUM(CASE WHEN LOWER(ar.status) = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN LOWER(ar.status) = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN LOWER(ar.status) = 'late' THEN 1 ELSE 0 END) as late_count
            ")
            ->groupByRaw($classLabel)
            ->orderByRaw($classLabel)
            ->get();

        // Get student details
        $studentDetails = DB::table('attendance_records as ar')
            ->join('students as s', 's.id', '=', 'ar.student_id')
            ->leftJoin('classes as c', 'c.id', '=', 's.class_id')
            ->selectRaw("
                {$classLabel} as class,
                COALESCE(s.fullname, CONCAT(s.first_name, ' ', s.last_name)) as student_name,
                s.username as student_code,
                ar.status,
                COUNT(*) as total_records,
                SUM(CASE WHEN LOWER(ar.status) = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN LOWER(ar.status) = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN LOWER(ar.status) = 'late' THEN 1 ELSE 0 END) as late_count
            ")
            ->groupByRaw("{$classLabel}, s.id, s.fullname, s.first_name, s.last_name, s.username, ar.status")
            ->orderByRaw("{$classLabel}, student_name")
            ->limit(500)
            ->get();

        // Generate CSV with multiple sections
        $fileName = 'education_attendance_report_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($classSummary, $studentDetails) {
            $output = fopen('php://output', 'w');

            // Section 1: Class Summary
            fputcsv($output, ['CLASS SUMMARY']);
            fputcsv($output, ['Generated:', now()->format('Y-m-d H:i:s')]);
            fputcsv($output, []);
            fputcsv($output, ['Class', 'Present', 'Absent', 'Late', 'Total', 'Attendance %']);

            foreach ($classSummary as $row) {
                $total = (int) $row->present_count + (int) $row->absent_count + (int) $row->late_count;
                $percentage = $total > 0 ? round(((int) $row->present_count / $total) * 100) : 0;
                fputcsv($output, [
                    $row->class,
                    (int) $row->present_count,
                    (int) $row->absent_count,
                    (int) $row->late_count,
                    $total,
                    $percentage . '%',
                ]);
            }

            // Section 2: Student Details
            fputcsv($output, []);
            fputcsv($output, ['STUDENT DETAILS']);
            fputcsv($output, []);
            fputcsv($output, ['Class', 'Student Name', 'Student Code', 'Present', 'Absent', 'Late', 'Total']);

            foreach ($studentDetails as $row) {
                fputcsv($output, [
                    $row->class,
                    $row->student_name,
                    $row->student_code,
                    (int) $row->present_count,
                    (int) $row->absent_count,
                    (int) $row->late_count,
                    (int) $row->total_records,
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

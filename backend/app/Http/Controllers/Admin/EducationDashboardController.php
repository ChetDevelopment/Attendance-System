<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AbsenceNotification;
use App\Models\AttendanceFollowUp;
use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EducationDashboardController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

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

    public function stats()
    {
        $today = Carbon::today()->toDateString();

        // OPTIMIZED: Single query to get all stats using conditional aggregation
        $stats = DB::table('attendance_records as ar')
            ->leftJoin('absence_notifications as an', 'an.attendance_record_id', '=', 'ar.id')
            ->selectRaw("
                COUNT(DISTINCT CASE WHEN an.status = 'active' AND an.absence_status = 'PENDING' THEN an.id END) as absent_today,
                COUNT(DISTINCT CASE WHEN LOWER(ar.status) = 'late' THEN ar.id END) as late_today,
                COUNT(DISTINCT CASE WHEN LOWER(ar.status) = 'absent' AND ar.attendance_date >= ? THEN ar.student_id END) as high_risk_students
            ", [now()->subDays(30)->toDateString()])
            ->where(function ($query) use ($today) {
                $query->whereDate('ar.attendance_date', $today)
                    ->orWhere(function ($fallback) use ($today) {
                        $fallback->whereNull('ar.attendance_date')
                            ->whereDate('ar.date', $today);
                    });
            })
            ->first();

        // OPTIMIZED: Separate query for pending follow-ups (simpler and faster)
        $pendingFollowUp = AbsenceNotification::query()
            ->where('status', 'active')
            ->where('absence_status', AbsenceNotification::STATUS_PENDING)
            ->count();

        return response()->json([
            'absentToday' => (int) $stats->absent_today,
            'lateToday' => (int) $stats->late_today,
            'highRisk' => (int) $stats->high_risk_students,
            'pendingFollowUp' => $pendingFollowUp,
        ]);
    }

    public function absentToday()
    {
        $today = Carbon::today()->toDateString();

        // OPTIMIZED: Use direct query instead of whereHas with OR conditions
        $rows = AbsenceNotification::query()
            ->with([
                'student:id,fullname,class,class_id,profile,face_image',
                'student.schoolClass:id,name',
                'attendanceRecord:id,attendance_date,date,status,submitted_by,session_id',
            ])
            ->where('status', 'active')
            ->whereHas('attendanceRecord', function ($query) use ($today) {
                $query->whereDate('attendance_date', $today);
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
        // OPTIMIZED: Use subquery to get high-risk students first, then join with students
        $highRiskStudentIds = AttendanceRecord::query()
            ->select('student_id')
            ->whereDate('attendance_date', '>=', now()->subDays(30)->toDateString())
            ->whereRaw('LOWER(status) = ?', ['absent'])
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) >= 3')
            ->pluck('student_id');

        $rows = AttendanceRecord::query()
            ->select('student_id', DB::raw('COUNT(*) as absence_count'), DB::raw('MAX(id) as latest_attendance_id'))
            ->whereIn('student_id', $highRiskStudentIds)
            ->whereDate('attendance_date', '>=', now()->subDays(30)->toDateString())
            ->whereRaw('LOWER(status) = ?', ['absent'])
            ->groupBy('student_id')
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
        // OPTIMIZED: Add date filtering to reduce data scanned
        $thirtyDaysAgo = now()->subDays(30)->toDateString();

        $rows = DB::table('attendance_records as ar')
            ->join('students as s', 's.id', '=', 'ar.student_id')
            ->leftJoin('classes as c', 'c.id', '=', 's.class_id')
            ->selectRaw("
                COALESCE(c.name, c.class_name, s.class, 'Unknown Class') as class,
                SUM(CASE WHEN LOWER(ar.status) = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN LOWER(ar.status) = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN LOWER(ar.status) = 'late' THEN 1 ELSE 0 END) as late_count
            ")
            ->whereDate('ar.attendance_date', '>=', $thirtyDaysAgo)
            ->groupByRaw("COALESCE(c.name, c.class_name, s.class, 'Unknown Class')")
            ->orderByRaw("COALESCE(c.name, c.class_name, s.class, 'Unknown Class')")
            ->get();

        return response()->json($rows);
    }

    public function reportAcademicYears()
    {
        $rows = AcademicYear::query()
            ->orderByDesc('is_active')
            ->orderByDesc(DB::raw('COALESCE(start_year, YEAR(start_date), id)'))
            ->orderByDesc('id')
            ->get()
            ->map(function (AcademicYear $year) {
                $derivedName = $year->name;

                if (!$derivedName && $year->start_year && $year->end_year) {
                    $derivedName = "{$year->start_year}-{$year->end_year}";
                }

                if (!$derivedName && $year->start_date && $year->end_date) {
                    $derivedName = Carbon::parse($year->start_date)->format('Y')
                        . '-'
                        . Carbon::parse($year->end_date)->format('Y');
                }

                return [
                    'id' => (int) $year->id,
                    'name' => $derivedName ?: "Academic Year {$year->id}",
                    'is_active' => (bool) $year->is_active,
                ];
            })
            ->values();

        return response()->json([
            'academic_years' => $rows,
            'active_academic_year_id' => $rows->firstWhere('is_active', true)['id'] ?? ($rows->first()['id'] ?? null),
        ]);
    }

    public function reportClasses(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
        ]);

        $query = SchoolClass::query()
            ->select('id', 'name', 'code', 'academic_year_id', 'is_active')
            ->where('is_active', true);

        if (!empty($validated['academic_year_id'])) {
            $query->where('academic_year_id', $validated['academic_year_id']);
        }

        $rows = $this->deduplicateReportClasses(
            $query->orderBy('name')->orderBy('code')->orderBy('id')->get()
        )
            ->map(function (SchoolClass $class) {
                $name = trim((string) $class->name) ?: 'Unnamed Class';
                $code = trim((string) ($class->code ?? ''));

                return [
                    'id' => (int) $class->id,
                    'name' => $name,
                    'code' => $code ?: null,
                    'academic_year_id' => $class->academic_year_id ? (int) $class->academic_year_id : null,
                    'label' => $code ? "{$name} ({$code})" : $name,
                ];
            })
            ->values();

        return response()->json([
            'classes' => $rows,
        ]);
    }

    public function reportStudents(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'period' => ['nullable', 'in:today,weekly,monthly'],
        ]);

        $period = $validated['period'] ?? 'today';
        [$startDate, $endDate] = $this->resolveReportDateRange($period);

        // OPTIMIZED: Use attendance_date directly instead of COALESCE
        $rows = DB::table('students as s')
            ->leftJoin('classes as c', 'c.id', '=', 's.class_id')
            ->leftJoin('attendance_records as ar', function ($join) use ($startDate, $endDate) {
                $join->on('ar.student_id', '=', 's.id')
                    ->whereBetween('ar.attendance_date', [$startDate->toDateString(), $endDate->toDateString()]);
            })
            ->when(!empty($validated['academic_year_id']), function ($query) use ($validated) {
                $query->where(function ($filter) use ($validated) {
                    $filter
                        ->where('c.academic_year_id', $validated['academic_year_id'])
                        ->orWhere('s.academic_year_id', $validated['academic_year_id']);
                });
            })
            ->when(!empty($validated['class_id']), function ($query) use ($validated) {
                $query->where('s.class_id', $validated['class_id']);
            })
            ->selectRaw("
                s.id,
                s.student_code,
                s.fullname,
                s.first_name,
                s.last_name,
                s.profile,
                s.face_image,
                COALESCE(c.name, s.class, 'Unknown Class') as class_name,
                c.code as class_code,
                SUM(CASE WHEN LOWER(ar.status) = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN LOWER(ar.status) = 'absent' THEN 1 ELSE 0 END) as absent_count
            ")
            ->groupBy(
                's.id',
                's.student_code',
                's.fullname',
                's.first_name',
                's.last_name',
                's.profile',
                's.face_image',
                'c.name',
                's.class',
                'c.code'
            )
            ->orderByRaw("COALESCE(c.name, s.class, 'Unknown Class')")
            ->orderByRaw("COALESCE(NULLIF(s.fullname, ''), TRIM(CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, ''))))")
            ->get()
            ->map(function ($student) {
                $name = trim((string) ($student->fullname ?? ''));

                if ($name === '') {
                    $name = trim(
                        implode(' ', array_filter([
                            $student->first_name ?? null,
                            $student->last_name ?? null,
                        ]))
                    );
                }

                return [
                    'id' => (int) $student->id,
                    'name' => $name ?: 'Unknown Student',
                    'student_code' => $student->student_code ?: '-',
                    'photo' => $this->buildStudentPhotoUrl($student),
                    'class_name' => $student->class_name,
                    'class_code' => $student->class_code,
                    'late_count' => (int) $student->late_count,
                    'absent_count' => (int) $student->absent_count,
                ];
            })
            ->values();

        return response()->json([
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'rows' => $rows,
        ]);
    }

    private function resolveReportDateRange(string $period): array
    {
        $today = Carbon::today();

        return match ($period) {
            'monthly' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'weekly' => [$today->copy()->startOfWeek(Carbon::MONDAY), $today->copy()->endOfWeek(Carbon::SUNDAY)],
            default => [$today->copy(), $today->copy()],
        };
    }

    private function deduplicateReportClasses($classes)
    {
        return $classes
            ->groupBy(fn($class) => ($class->academic_year_id ?? 'null') . ':' . trim((string) $class->name))
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
            ->map(fn(AttendanceFollowUp $followUp) => [
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

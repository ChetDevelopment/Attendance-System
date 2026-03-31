<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Student;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BiometricManagementController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService)
    {
    }

    public function overview()
    {
        $totalStudents = Student::count();
        $enrolledCount = Student::where('fingerprint_enrolled', true)->count();
        $rfidAssignedCount = Student::whereNotNull('card_id')->count();
        $recentActivity = Student::query()
            ->whereNotNull('last_biometric_scan')
            ->with('class:id,class_name')
            ->latest('last_biometric_scan')
            ->limit(8)
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->fullname ?: trim($student->first_name . ' ' . $student->last_name),
                'student_code' => $student->student_code ?: $student->username,
                'class_name' => $student->class?->class_name ?? $student->class,
                'last_biometric_scan' => optional($student->last_biometric_scan)->toDateTimeString(),
            ]);

        return response()->json([
            'summary' => [
                'total_students' => $totalStudents,
                'fingerprint_enrolled' => $enrolledCount,
                'rfid_assigned' => $rfidAssignedCount,
                'pending_enrollment' => max(0, $totalStudents - $enrolledCount),
                'enrollment_percentage' => $totalStudents > 0 ? round(($enrolledCount / $totalStudents) * 100, 2) : 0,
            ],
            'device_status' => [
                'fingerprint_scanner' => 'Ready',
                'rfid_reader' => 'Ready',
                'connection' => 'Online',
            ],
            'recent_activity' => $recentActivity,
        ]);
    }

    public function students(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'enrollment' => ['nullable', 'in:all,enrolled,not_enrolled'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Student::query()
            ->with('class:id,class_name')
            ->select([
                'id',
                'student_code',
                'fullname',
                'first_name',
                'last_name',
                'username',
                'email',
                'class',
                'class_id',
                'card_id',
                'fingerprint_enrolled',
                'last_biometric_scan',
                'profile',
                'is_active',
            ])
            ->orderBy('fullname');

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('fullname', 'like', "%{$search}%")
                    ->orWhere('student_code', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('card_id', 'like', "%{$search}%");
            });
        }

        if (($validated['enrollment'] ?? 'all') === 'enrolled') {
            $query->where('fingerprint_enrolled', true);
        }

        if (($validated['enrollment'] ?? 'all') === 'not_enrolled') {
            $query->where(function ($builder) {
                $builder->where('fingerprint_enrolled', false)
                    ->orWhereNull('fingerprint_enrolled');
            });
        }

        $students = $query->paginate((int) ($validated['per_page'] ?? 15));

        $students->getCollection()->transform(function (Student $student) {
            // Build profile URL
            $profileUrl = null;
            $profileField = $student->profile;
            if ($profileField) {
                if (str_starts_with($profileField, 'http')) {
                    $profileUrl = $profileField;
                } else {
                    $profileUrl = config('app.frontend_url', 'http://localhost:5173') . '/' . $profileField;
                }
            }
            
            return [
                'id' => $student->id,
                'name' => $student->fullname ?: trim($student->first_name . ' ' . $student->last_name),
                'student_code' => $student->student_code ?: $student->username,
                'email' => $student->email,
                'class_name' => $student->class?->class_name ?? $student->class,
                'card_id' => $student->card_id,
                'fingerprint_enrolled' => (bool) $student->fingerprint_enrolled,
                'last_biometric_scan' => optional($student->last_biometric_scan)->toDateTimeString(),
                'profile' => $student->profile,
                'avatar' => $profileUrl,
                'is_active' => (bool) $student->is_active,
            ];
        });

        return response()->json($students);
    }

    public function history(Student $student)
    {
        $historyQuery = AttendanceRecord::query()
            ->with('session:id,name,start_time,end_time')
            ->where('student_id', $student->id)
            ->orderByDesc('attendance_date');

        if (Schema::hasColumn('attendance_records', 'check_in_time')) {
            $historyQuery->orderByDesc('check_in_time');
        } else {
            $historyQuery->orderByDesc('created_at');
        }

        $history = $historyQuery
            ->limit(20)
            ->get()
            ->map(function (AttendanceRecord $record) use ($student) {
                return [
                    'id' => $record->id,
                    'attendance_date' => optional($record->attendance_date)->toDateString(),
                    'status' => strtoupper((string) $record->status),
                    'check_in_time' => optional($record->check_in_time)->toDateTimeString(),
                    'session_name' => $record->session?->name ?? 'Session',
                    'scan_method' => $student->fingerprint_enrolled ? 'Fingerprint' : ($student->card_id ? 'RFID Card' : 'Manual'),
                ];
            });

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->fullname ?: trim($student->first_name . ' ' . $student->last_name),
                'student_code' => $student->student_code ?: $student->username,
                'card_id' => $student->card_id,
                'fingerprint_enrolled' => (bool) $student->fingerprint_enrolled,
            ],
            'history' => $history,
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'card_id' => ['nullable', 'string', 'max:255', 'unique:students,card_id,' . $student->id],
            'fingerprint_enrolled' => ['required', 'boolean'],
            'last_biometric_scan' => ['nullable', 'date'],
        ]);

        if (!$validated['fingerprint_enrolled']) {
            $validated['fingerprint_template'] = null;
        }

        $student->update($validated);

        $this->activityLogService->recordFromRequest(
            $request->user(),
            $request,
            'Updated biometric enrollment',
            'Updated biometric settings for student #' . $student->id
        );

        return response()->json([
            'message' => 'Biometric settings updated successfully.',
            'student' => $student->fresh(),
        ]);
    }
}

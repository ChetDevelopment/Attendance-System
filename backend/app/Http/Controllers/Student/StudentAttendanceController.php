<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceFollowUp;
use App\Models\AttendanceRecord;
use App\Models\BiometricScan;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class StudentAttendanceController extends Controller
{
    private function baseStudentQuery()
    {
        return Student::query()
            ->with('class:id,class_name');
    }

    private function formatTimeValue($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable $exception) {
            return (string) $value;
        }
    }

    public function history(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);

        if (!$student) {
            return response()->json([], Response::HTTP_OK);
        }

        $rows = AttendanceRecord::query()
            ->with(['session:id,name,start_time,end_time'])
            ->where('student_id', $student->id)
            ->orderByDesc('attendance_date')
            ->orderByDesc('check_in_time')
            ->limit((int) $request->integer('limit', 50))
            ->get()
            ->map(function (AttendanceRecord $record) {
                $status = strtoupper((string) $record->status);
                $session = $record->session;

                return [
                    'id' => (string) $record->id,
                    'studentId' => (string) $record->student_id,
                    'status' => in_array($status, ['PRESENT', 'ABSENT', 'LATE', 'PENDING'], true)
                        ? $status
                        : 'PENDING',
                    'date' => optional($record->attendance_date)->format('Y-m-d'),
                    'timeSlot' => $session
                        ? trim(collect([
                            $this->formatTimeValue($session->start_time),
                            $this->formatTimeValue($session->end_time),
                        ])->filter()->join(' - '))
                        : '',
                    'courseName' => (string) ($session->name ?? 'Attendance Session'),
                    'timestamp' => optional($record->check_in_time)->toIso8601String(),
                ];
            })
            ->values();

        return response()->json($rows);
    }

    /**
     * Receive scanned card id (RFID/NFC) and mark attendance for a session.
     *
     * Expected payload (frontend): { sessionId, cardData }
     */
    public function cardScan(Request $request)
    {
        $validated = $request->validate([
            'sessionId' => ['nullable', 'integer', 'exists:sessions,id'],
            'cardData' => ['required', 'string', 'max:255'],
        ]);

        $session = $this->resolveSession($validated['sessionId'] ?? null);
        if (!$session) {
            return response()->json([
                'message' => 'No active session found.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $student = $this->baseStudentQuery()
            ->where('card_id', $validated['cardData'])
            ->first();

        if (!$student) {
            return response()->json([
                'message' => 'Student not found for this card id.',
            ], 404);
        }

        if (!$student->is_active) {
            return response()->json([
                'message' => 'Student is inactive.',
            ], 403);
        }

        $now = Carbon::now();
        $attendanceDate = $now->toDateString();

        // Determine late vs present using session rules (defaults to "present").
        $status = 'present';
        if (!is_null($session->start_time)) {
            try {
                $startTime = Carbon::parse($attendanceDate . ' ' . $session->start_time);
                $lateAfterMinutes = (int) ($session->late_after_minutes ?? 0);
                $lateAfter = $startTime->copy()->addMinutes(max(0, $lateAfterMinutes));
                if ($now->gt($lateAfter)) {
                    $status = 'late';
                }
            } catch (\Exception $e) {
                // If parsing fails, keep default status.
            }
        }

        // Ensure a parent attendance row exists for reporting/uniqueness per class/session/date.
        Attendance::firstOrCreate(
            [
                'class_id' => $student->class_id,
                'session_id' => $session->id,
                'date' => $attendanceDate,
            ],
            [
                'submitted_by' => auth()->id(),
                'is_locked' => false,
            ]
        );

        $record = AttendanceRecord::where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->first();

        if ($record) {
            $this->logBiometricScan($student->id, $session->id, BiometricScan::SCAN_TYPE_CARD, $validated['cardData'], BiometricScan::STATUS_DUPLICATE, 'Already checked in');

            return response()->json([
                'message' => 'Already checked in for this session today.',
                'student' => $this->studentPayload($student),
                'attendance_record' => $record,
            ]);
        }

        $record = AttendanceRecord::create([
            'student_id' => $student->id,
            'session_id' => $session->id,
            'submitted_by' => auth()->id(),
            'attendance_date' => $attendanceDate,
            'status' => $status,
            'check_in_time' => $now,
        ]);

        $this->logBiometricScan($student->id, $session->id, BiometricScan::SCAN_TYPE_CARD, $validated['cardData'], BiometricScan::STATUS_SUCCESS);

        return response()->json([
            'message' => 'Attendance recorded successfully.',
            'student' => $this->studentPayload($student),
            'attendance_record' => $record,
        ], 201);
    }

    public function fingerprintScan(Request $request)
    {
        $validated = $request->validate([
            'sessionId' => ['nullable', 'integer', 'exists:sessions,id'],
            'fingerprintData' => ['required', 'string', 'max:255'],
        ]);

        $student = $this->resolveStudentFromRequest($request);
        $session = $this->resolveSession($validated['sessionId'] ?? null);

        if (!$student || !$session) {
            return response()->json([
                'message' => 'Student or active session not found.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$student->fingerprint_enrolled) {
            $this->logBiometricScan($student->id, $session->id, BiometricScan::SCAN_TYPE_FINGERPRINT, $validated['fingerprintData'], BiometricScan::STATUS_INVALID, 'Fingerprint not enrolled');

            return response()->json([
                'message' => 'Fingerprint not enrolled. Please contact administrator.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->createStudentAttendanceFromScan($request, $student, $session, BiometricScan::SCAN_TYPE_FINGERPRINT, $validated['fingerprintData']);
    }

    public function validateBiometric(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sessionId' => ['nullable', 'integer', 'exists:sessions,id'],
            'scanType' => ['required', 'in:card,fingerprint'],
            'scanData' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $student = $this->resolveStudentFromRequest($request);
        $session = $this->resolveSession($request->input('sessionId'));

        if (!$student || !$session) {
            return response()->json([
                'message' => 'Student or active session not found.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $scanType = $request->string('scanType')->toString();
        $scanData = $request->string('scanData')->toString();

        if ($scanType === BiometricScan::SCAN_TYPE_CARD) {
            if (!$student->card_id || $student->card_id !== $scanData) {
                $this->logBiometricScan($student->id, $session->id, $scanType, $scanData, BiometricScan::STATUS_INVALID, 'Invalid card');

                return response()->json([
                    'message' => 'Invalid card. Please try again.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        if ($scanType === BiometricScan::SCAN_TYPE_FINGERPRINT) {
            if (!$student->fingerprint_enrolled) {
                $this->logBiometricScan($student->id, $session->id, $scanType, $scanData, BiometricScan::STATUS_INVALID, 'Fingerprint not enrolled');

                return response()->json([
                    'message' => 'Fingerprint not enrolled. Please contact administrator.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        if (BiometricScan::hasRecentDuplicate($student->id, $session->id, $scanType)) {
            return response()->json([
                'message' => 'Please wait before scanning again.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return response()->json([
            'message' => 'Biometric validation successful.',
            'student' => $this->studentPayload($student),
            'session' => [
                'id' => $session->id,
                'name' => $session->name,
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
            ],
        ], Response::HTTP_OK);
    }

    public function biometricHistory(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);

        if (!$student) {
            return response()->json([], Response::HTTP_OK);
        }

        $rows = BiometricScan::query()
            ->with('session:id,name,start_time,end_time')
            ->where('student_id', $student->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (BiometricScan $scan) {
                return [
                    'id' => $scan->id,
                    'scan_type' => $scan->scan_type,
                    'status' => $scan->status,
                    'failure_reason' => $scan->failure_reason,
                    'session_name' => $scan->session?->name,
                    'created_at' => optional($scan->created_at)->toIso8601String(),
                ];
            })
            ->values();

        return response()->json($rows, Response::HTTP_OK);
    }

    public function biometricStatus(Request $request)
    {
        $student = $this->resolveStudentFromRequest($request);
        $session = $this->resolveSession();

        return response()->json([
            'scanner' => 'online',
            'connection' => 'active',
            'currentSession' => $session ? [
                'id' => $session->id,
                'name' => $session->name,
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
            ] : null,
            'student' => $student ? [
                'card_enrolled' => !empty($student->card_id),
                'fingerprint_enrolled' => (bool) $student->fingerprint_enrolled,
                'last_biometric_scan' => optional($student->last_biometric_scan)->toIso8601String(),
                'today_scan_count' => BiometricScan::getTodayScanCount($student->id),
            ] : null,
        ], Response::HTTP_OK);
    }

    public function studentInfo(Request $request)
    {
        $student = $this->resolveStudentFromScanRequest($request) ?? $this->resolveStudentFromRequest($request);

        if (!$student) {
            return response()->json([
                'message' => 'Student not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($this->studentPayload($student), Response::HTTP_OK);
    }

    public function submitAttendance(Request $request)
    {
        $validated = $request->validate([
            'sessionId' => ['nullable', 'integer', 'exists:sessions,id'],
            'method' => ['nullable', 'in:photo,qrcode,manual'],
            'photo' => ['nullable', 'string'],
            'qrCode' => ['nullable', 'string'],
        ]);

        $student = $this->resolveStudentFromRequest($request);
        $session = $this->resolveSession($validated['sessionId'] ?? null);

        if (!$student || !$session) {
            return response()->json([
                'message' => 'Student or active session not found.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $scanType = ($validated['method'] ?? 'photo') === 'qrcode'
            ? BiometricScan::SCAN_TYPE_CARD
            : BiometricScan::SCAN_TYPE_FINGERPRINT;

        $scanData = $validated['qrCode'] ?? $validated['photo'] ?? 'student-self-check-in';

        return $this->createStudentAttendanceFromScan($request, $student, $session, $scanType, $scanData);
    }

    public function requestManual(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sessionId' => ['nullable', 'integer', 'exists:sessions,id'],
            'reason' => ['required', 'string', 'min:10'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $student = $this->resolveStudentFromRequest($request);
        $session = $this->resolveSession($request->input('sessionId'));

        if (!$student || !$session) {
            return response()->json([
                'message' => 'Student or active session not found.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $attendanceDate = now()->toDateString();

        $attendanceRecord = AttendanceRecord::firstOrCreate(
            [
                'student_id' => $student->id,
                'session_id' => $session->id,
                'attendance_date' => $attendanceDate,
            ],
            [
                'submitted_by' => $this->resolveUserFromRequest($request)?->id,
                'status' => 'Pending',
            ]
        );

        $existingRequest = AttendanceFollowUp::query()
            ->where('attendance_record_id', $attendanceRecord->id)
            ->where('resolved', false)
            ->first();

        if ($existingRequest) {
            return response()->json([
                'message' => 'A manual request is already pending for this session.',
            ], Response::HTTP_CONFLICT);
        }

        $followUp = AttendanceFollowUp::create([
            'attendance_record_id' => $attendanceRecord->id,
            'updated_by' => $this->resolveUserFromRequest($request)?->id,
            'reason' => 'student_manual_request',
            'comment' => $request->string('reason')->toString(),
            'note' => $request->string('reason')->toString(),
            'status' => 'pending',
            'resolved' => false,
            'is_excused' => false,
        ]);

        return response()->json([
            'message' => 'Manual attendance request submitted successfully.',
            'request' => [
                'id' => $followUp->id,
                'attendance_record_id' => $attendanceRecord->id,
                'session_id' => $session->id,
                'status' => $followUp->status,
                'submitted_at' => optional($followUp->created_at)->toIso8601String(),
            ],
        ], Response::HTTP_OK);
    }

    private function createStudentAttendanceFromScan(Request $request, Student $student, Session $session, string $scanType, string $scanData)
    {
        $attendanceDate = now()->toDateString();

        $existing = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->first();

        if ($existing) {
            $this->logBiometricScan($student->id, $session->id, $scanType, $scanData, BiometricScan::STATUS_DUPLICATE, 'Already checked in');

            return response()->json([
                'message' => 'Attendance already recorded for this session.',
                'student' => $this->studentPayload($student),
                'attendance_record' => $existing,
            ], Response::HTTP_OK);
        }

        $now = Carbon::now();
        $status = $session->isLate($now) ? 'Late' : 'Present';

        Attendance::firstOrCreate(
            [
                'class_id' => $student->class_id,
                'session_id' => $session->id,
                'date' => $attendanceDate,
            ],
            [
                'submitted_by' => $this->resolveUserFromRequest($request)?->id,
                'is_locked' => false,
            ]
        );

        $record = AttendanceRecord::create([
            'student_id' => $student->id,
            'session_id' => $session->id,
            'submitted_by' => $this->resolveUserFromRequest($request)?->id,
            'attendance_date' => $attendanceDate,
            'status' => $status,
            'check_in_time' => $now,
            'location' => 'Student ' . ucfirst($scanType) . ' Scan',
        ]);

        $this->logBiometricScan($student->id, $session->id, $scanType, $scanData, BiometricScan::STATUS_SUCCESS);

        $student->forceFill([
            'last_biometric_scan' => now(),
        ])->save();

        return response()->json([
            'message' => 'Attendance recorded successfully.',
            'student' => $this->studentPayload($student),
            'attendance_record' => $record,
        ], Response::HTTP_CREATED);
    }

    private function logBiometricScan(int $studentId, ?int $sessionId, string $scanType, ?string $scanData, string $status, ?string $failureReason = null): void
    {
        BiometricScan::create([
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'scan_type' => $scanType,
            'scan_data' => $scanData,
            'status' => $status,
            'failure_reason' => $failureReason,
            'ip_address' => request()->ip(),
        ]);
    }

    private function studentPayload(Student $student): array
    {
        $className = $student->class?->class_name ?? $student->class ?? 'N/A';

        return [
            'id' => (string) $student->id,
            'student_code' => $student->student_code,
            'name' => $student->fullname ?: trim($student->first_name . ' ' . $student->last_name),
            'class' => $className,
            'card_id' => $student->card_id,
            'fingerprint_enrolled' => (bool) $student->fingerprint_enrolled,
            'enrollmentDate' => optional($student->created_at)->format('Y-m-d'),
        ];
    }

    private function resolveStudentFromRequest(Request $request): ?Student
    {
        $user = $this->resolveUserFromRequest($request);

        if ($user?->student_id) {
            return $this->baseStudentQuery()->find($user->student_id);
        }

        if ($user?->email) {
            return $this->baseStudentQuery()->where('email', $user->email)->first();
        }

        if ($request->hasHeader('X-Student-Session')) {
            return $this->baseStudentQuery()->where('card_id', $request->header('X-Student-Session'))->first();
        }

        return null;
    }

    private function resolveStudentFromScanRequest(Request $request): ?Student
    {
        $scanType = $request->string('scanType')->toString();
        $scanData = trim($request->string('scanData')->toString());

        if ($scanType === BiometricScan::SCAN_TYPE_CARD && $scanData !== '') {
            return $this->baseStudentQuery()->where('card_id', $scanData)->first();
        }

        return null;
    }

    private function resolveUserFromRequest(Request $request): ?User
    {
        $user = $request->user();
        return $user instanceof User ? $user : null;
    }

    private function resolveSession(?int $sessionId = null): ?Session
    {
        if ($sessionId) {
            return Session::find($sessionId);
        }

        $currentTime = Carbon::now(config('sessions.timezone', 'Asia/Bangkok'))->format('H:i:s');

        return Session::query()
            ->active()
            ->ordered()
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->first()
            ?? Session::query()->active()->ordered()->first();
    }
}

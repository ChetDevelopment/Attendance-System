<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\Session;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentAttendanceController extends Controller
{
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
        $user = $request->user();
        $studentId = (int) ($user?->student_id ?? 0);

        if ($studentId <= 0) {
            return response()->json([
                'message' => 'Student account is not linked to a student record.',
            ], 403);
        }

        $rows = AttendanceRecord::query()
            ->with(['session:id,name,start_time,end_time'])
            ->where('student_id', $studentId)
            ->orderByDesc('attendance_date')
            ->orderByDesc('check_in_time')
            ->limit(50)
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
            'sessionId' => ['required', 'integer', 'exists:sessions,id'],
            'cardData' => ['required', 'string', 'max:255'],
        ]);

        $session = Session::findOrFail($validated['sessionId']);

        $student = Student::query()
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
            return response()->json([
                'message' => 'Already checked in for this session today.',
                'student' => [
                    'id' => $student->id,
                    'student_code' => $student->student_code,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'class_id' => $student->class_id,
                    'card_id' => $student->card_id,
                ],
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

        return response()->json([
            'message' => 'Attendance recorded successfully.',
            'student' => [
                'id' => $student->id,
                'student_code' => $student->student_code,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'class_id' => $student->class_id,
                'card_id' => $student->card_id,
            ],
            'attendance_record' => $record,
        ], 201);
    }
}

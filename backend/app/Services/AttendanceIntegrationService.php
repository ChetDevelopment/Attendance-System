<?php

namespace App\Services;

use App\Models\AbsenceNotification;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class AttendanceIntegrationService
{
    public function ensureParentAttendance(AttendanceRecord $record): ?Attendance
    {
        if ($record->attendance_id) {
            return $record->attendance;
        }

        $recordDate = $record->attendance_date ?? $record->date;
        $student = $record->student;

        if (!$recordDate || !$student?->class_id) {
            return null;
        }

        $attendance = Attendance::firstOrCreate(
            [
                'class_id' => $student->class_id,
                'session_id' => $record->session_id,
                'date' => Carbon::parse($recordDate)->toDateString(),
            ],
            [
                'submitted_by' => $record->submitted_by,
                'is_locked' => false,
            ]
        );

        if ($record->attendance_id !== $attendance->id) {
            $record->attendance_id = $attendance->id;
            $record->save();
        }

        return $attendance;
    }

    public function syncAttendanceRecord(AttendanceRecord $record): void
    {
        $this->ensureParentAttendance($record);

        $status = strtoupper((string) $record->status);

        if ($status === 'ABSENT') {
            AbsenceNotification::updateOrCreate(
                ['attendance_record_id' => $record->id],
                [
                    'student_id' => $record->student_id,
                    'session_id' => $record->session_id,
                    'absence_status' => AbsenceNotification::STATUS_PENDING,
                    'status' => 'active',
                ]
            );

            return;
        }

        AbsenceNotification::where('attendance_record_id', $record->id)->update([
            'status' => 'inactive',
            'status_updated_at' => now(),
        ]);
    }
}

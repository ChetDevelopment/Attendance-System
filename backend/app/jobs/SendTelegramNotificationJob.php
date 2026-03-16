<?php

namespace App\Jobs;

use App\Models\AbsenceNotification;
use App\Models\AttendanceRecord;
use App\Models\Session;
use App\Models\Student;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue Job for sending Telegram notifications asynchronously
 */
class SendTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * The maximum number of seconds the job should run.
     */
    public int $timeout = 120;

    /**
     * Student ID
     */
    private int $studentId;

    /**
     * Session ID
     */
    private int $sessionId;

    /**
     * Attendance record ID
     */
    private ?int $attendanceRecordId;

    /**
     * Create a new job instance.
     *
     * @param int $studentId
     * @param int $sessionId
     * @param int|null $attendanceRecordId
     */
    public function __construct(int $studentId, int $sessionId, ?int $attendanceRecordId = null)
    {
        $this->studentId = $studentId;
        $this->sessionId = $sessionId;
        $this->attendanceRecordId = $attendanceRecordId;
        $this->onQueue('telegram_notifications');
    }

    /**
     * Get unique ID for the job (prevents duplicate notifications)
     */
    public function uniqueId(): string
    {
        return "telegram_notification_{$this->studentId}_{$this->sessionId}";
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramService $telegramService): void
    {
        $student = Student::find($this->studentId);
        $session = Session::find($this->sessionId);
        
        if (!$student) {
            Log::warning('SendTelegramNotificationJob: Student not found', [
                'student_id' => $this->studentId,
            ]);
            return;
        }

        // Get or create attendance record
        $attendance = null;
        if ($this->attendanceRecordId) {
            $attendance = AttendanceRecord::find($this->attendanceRecordId);
        } else {
            $attendance = AttendanceRecord::where('student_id', $this->studentId)
                ->where('session_id', $this->sessionId)
                ->first();
        }

        // Check if notification already sent
        if ($attendance) {
            $existingNotification = AbsenceNotification::where('attendance_record_id', $attendance->id)
                ->where('notification_type', 'telegram')
                ->where('status', 'sent')
                ->first();

            if ($existingNotification) {
                Log::info('SendTelegramNotificationJob: Notification already sent', [
                    'student_id' => $this->studentId,
                    'session_id' => $this->sessionId,
                ]);
                return;
            }
        }

        // Send Telegram notification
        $result = $telegramService->sendAbsenceAlert(
            $student->fullname ?? $student->username ?? 'Unknown',
            $student->username ?? (string) $student->id,
            $student->class ?? 'Unknown Class',
            $attendance?->date ?? now()->toDateString(),
            $session ? "{$session->start_time} - {$session->end_time}" : 'N/A',
            $attendance?->id
        );

        // Create notification record
        if ($attendance) {
            AbsenceNotification::create([
                'attendance_record_id' => $attendance->id,
                'student_id' => $student->id,
                'session_id' => $session?->id,
                'notification_type' => 'telegram',
                'status' => $result['success'] ? 'sent' : 'failed',
                'telegram_message_id' => $result['message_id'] ?? null,
                'error_message' => $result['error'] ?? null,
                'sent_at' => $result['success'] ? now() : null,
            ]);
        }

        if ($result['success']) {
            Log::info('SendTelegramNotificationJob: Notification sent successfully', [
                'student_id' => $this->studentId,
                'session_id' => $this->sessionId,
                'message_id' => $result['message_id'],
            ]);
        } else {
            Log::error('SendTelegramNotificationJob: Failed to send notification', [
                'student_id' => $this->studentId,
                'session_id' => $this->sessionId,
                'error' => $result['error'],
            ]);
            
            // Re-throw to trigger retry
            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to send Telegram notification');
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendTelegramNotificationJob: Job failed', [
            'student_id' => $this->studentId,
            'session_id' => $this->sessionId,
            'error' => $exception->getMessage(),
        ]);

        // Create failed notification record if we have attendance
        if ($this->attendanceRecordId) {
            $attendance = AttendanceRecord::find($this->attendanceRecordId);
            if ($attendance) {
                AbsenceNotification::create([
                    'attendance_record_id' => $attendance->id,
                    'student_id' => $this->studentId,
                    'session_id' => $this->sessionId,
                    'notification_type' => 'telegram',
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'telegram',
            'notification',
            'student:' . $this->studentId,
            'session:' . $this->sessionId,
        ];
    }
}

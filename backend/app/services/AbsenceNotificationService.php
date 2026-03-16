<?php

namespace App\Services;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\AbsenceNotification;
use App\Models\AttendanceRecord;
use App\Models\Session;
use App\Models\Student;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AbsenceNotificationService
{
    private array $config;

    public function __construct(private readonly TelegramService $telegramService)
    {
        $this->config = [
            'education_api_url' => config('services.education_department.api_url', env('EDUCATION_API_URL')),
            'education_api_key' => config('services.education_department.api_key', env('EDUCATION_API_KEY')),
            'education_enabled' => config('services.education_department.enabled', env('EDUCATION_DEPARTMENT_ENABLED', false)),
            'telegram_enabled' => config('services.telegram.enabled', true),
        ];
    }

    /**
     * Process all absences for a session and send notifications
     */
    public function processSessionAbsences(
        int $sessionId,
        ?bool $sendTelegram = null,
        ?bool $sendEducationApi = null
    ): array
    {
        $session = Session::find($sessionId);
        
        if (!$session) {
            return [
                'success' => false,
                'error' => 'Session not found',
            ];
        }

        // Get all students who should attend this session
        $expectedStudents = $this->getExpectedStudents($session);
        
        // Get students who were marked present/late
        $attendedStudentIds = AttendanceRecord::where('session_id', $sessionId)
            ->whereIn('status', ['Present', 'Late', 'present', 'late'])
            ->pluck('student_id')
            ->toArray();

        // Find absent students
        $absentStudents = array_diff($expectedStudents, $attendedStudentIds);
        
        $results = [
            'total_expected' => count($expectedStudents),
            'total_present' => count($attendedStudentIds),
            'total_absent' => count($absentStudents),
            'notifications' => [],
        ];

        $enableTelegram = $sendTelegram ?? (bool) $this->config['telegram_enabled'];
        $enableEducationApi = $sendEducationApi ?? (bool) $this->config['education_enabled'];

        $studentMap = Student::whereIn('id', $absentStudents)->get()->keyBy('id');
        $sessionMap = collect([$sessionId => $session]);

        // Send notifications for each absent student
        foreach ($absentStudents as $studentId) {
            $notificationResult = $this->sendAbsenceNotifications(
                $studentId,
                $sessionId,
                $enableTelegram,
                $enableEducationApi,
                $studentMap,
                $sessionMap
            );
            $results['notifications'][] = $notificationResult;
        }

        $results['success'] = true;
        
        Log::info('Session absence notifications processed', [
            'session_id' => $sessionId,
            'absent_count' => count($absentStudents),
        ]);

        return $results;
    }

    /**
     * Get list of student IDs expected to attend a session
     */
    private function getExpectedStudents(Session $session): array
    {
        // Get students by class from the session
        $students = Student::where('class_id', $session->class_id)
            ->orWhere('class', $session->name)
            ->pluck('id')
            ->toArray();

        // If no class-based students, get all active students
        if (empty($students)) {
            $students = Student::pluck('id')->toArray();
        }

        return $students;
    }

    /**
     * Send notifications for a single absent student
     */
    public function sendAbsenceNotifications(
        int $studentId,
        int $sessionId,
        ?bool $sendTelegram = null,
        ?bool $sendEducationApi = null,
        ?Collection $studentMap = null,
        ?Collection $sessionMap = null
    ): array
    {
        $student = $studentMap?->get($studentId) ?? Student::find($studentId);
        $session = $sessionMap?->get($sessionId) ?? Session::find($sessionId);

        if (!$student) {
            return [
                'success' => false,
                'error' => 'Student not found',
            ];
        }

        // Get or create attendance record for the absent student
        $attendance = AttendanceRecord::firstOrCreate(
            [
                'student_id' => $studentId,
                'session_id' => $sessionId,
            ],
            [
                'status' => 'Absent',
                'date' => now()->toDateString(),
                'location' => 'Auto-marked by system',
            ]
        );

        $results = [
            'student_id' => $studentId,
            'student_name' => $student->fullname ?? $student->username,
            'attendance_record_id' => $attendance->id,
            'telegram' => null,
            'education_department' => null,
        ];

        $enableTelegram = $sendTelegram ?? (bool) $this->config['telegram_enabled'];
        $enableEducationApi = $sendEducationApi ?? (bool) $this->config['education_enabled'];

        // Send Telegram notification
        if ($enableTelegram) {
            $results['telegram'] = $this->sendTelegramNotification($student, $session, $attendance);
        }

        // Send Education Department notification
        if ($enableEducationApi) {
            $results['education_department'] = $this->sendEducationDepartmentNotification($student, $session, $attendance);
        }

        return $results;
    }

    /**
     * Send Telegram notification for absence (async via queue)
     */
    private function sendTelegramNotification(Student $student, ?Session $session, AttendanceRecord $attendance): array
    {
        // Check for duplicate notification
        $existingNotification = AbsenceNotification::where('attendance_record_id', $attendance->id)
            ->where('notification_type', 'telegram')
            ->where('status', 'sent')
            ->first();

        if ($existingNotification) {
            return [
                'success' => false,
                'error' => 'Notification already sent',
                'duplicate' => true,
            ];
        }

        // Dispatch job to queue for async processing
        try {
            SendTelegramNotificationJob::dispatch(
                $student->id,
                $session?->id,
                $attendance->id
            );

            Log::info('Telegram notification job dispatched', [
                'student_id' => $student->id,
                'session_id' => $session?->id,
                'attendance_record_id' => $attendance->id,
            ]);

            return [
                'success' => true,
                'queued' => true,
                'message' => 'Notification queued for processing',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to dispatch Telegram notification job', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to queue notification: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send notification to Education Department API
     */
    private function sendEducationDepartmentNotification(Student $student, ?Session $session, AttendanceRecord $attendance): array
    {
        // Check for duplicate notification
        $existingNotification = AbsenceNotification::where('attendance_record_id', $attendance->id)
            ->where('notification_type', 'education_api')
            ->where('status', 'sent')
            ->first();

        if ($existingNotification) {
            return [
                'success' => false,
                'error' => 'Notification already sent',
                'duplicate' => true,
            ];
        }

        if (empty($this->config['education_api_url'])) {
            $result = [
                'success' => false,
                'error' => 'Education API URL not configured',
            ];
        } else {
            try {
                $response = Http::timeout(30)->withHeaders([
                    'Authorization' => 'Bearer ' . $this->config['education_api_key'],
                    'Content-Type' => 'application/json',
                ])->post($this->config['education_api_url'], [
                    'student_id' => $student->id,
                    'student_name' => $student->fullname ?? $student->username,
                    'student_code' => $student->username,
                    'class' => $student->class ?? 'Unknown',
                    'session_id' => $session?->id,
                    'session_name' => $session?->name,
                    'date' => $attendance->date ?? now()->format('Y-m-d'),
                    'status' => 'absent',
                    'notification_type' => 'absence_alert',
                ]);

                $result = $response->successful() ? [
                    'success' => true,
                    'response' => $response->json(),
                ] : [
                    'success' => false,
                    'error' => $response->body(),
                ];
            } catch (\Exception $e) {
                $result = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Create notification record
        AbsenceNotification::create([
            'attendance_record_id' => $attendance->id,
            'student_id' => $student->id,
            'session_id' => $session?->id,
            'notification_type' => 'education_api',
            'status' => $result['success'] ? 'sent' : 'failed',
            'error_message' => $result['error'] ?? null,
            'sent_at' => $result['success'] ? now() : null,
        ]);

        return $result;
    }

    /**
     * Retry failed notifications
     */
    public function retryFailedNotifications(
        ?int $sessionId = null,
        array $notificationTypes = ['telegram', 'education_api'],
        int $limit = 10
    ): array
    {
        $query = AbsenceNotification::where('status', 'failed')
            ->where('created_at', '>=', now()->subDays(7))
            ->whereIn('notification_type', $notificationTypes);

        if ($sessionId !== null) {
            $query->where('session_id', $sessionId);
        }

        $failedNotifications = $query
            ->limit($limit)
            ->get();

        $results = [
            'total' => $failedNotifications->count(),
            'retried' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'success' => true,
        ];

        $studentMap = Student::whereIn('id', $failedNotifications->pluck('student_id')->filter()->unique()->values())
            ->get()
            ->keyBy('id');
        $sessionMap = Session::whereIn('id', $failedNotifications->pluck('session_id')->filter()->unique()->values())
            ->get()
            ->keyBy('id');
        $attendanceMap = AttendanceRecord::whereIn('id', $failedNotifications->pluck('attendance_record_id')->filter()->unique()->values())
            ->get()
            ->keyBy('id');

        foreach ($failedNotifications as $notification) {
            $student = $studentMap->get($notification->student_id);
            $session = $sessionMap->get($notification->session_id);
            $attendance = $attendanceMap->get($notification->attendance_record_id);

            if (!$student || !$attendance) {
                $notification->update(['status' => 'failed', 'error_message' => 'Student or attendance not found']);
                $results['failed']++;
                $results['success'] = false;
                continue;
            }

            if ($notification->notification_type === 'telegram') {
                $result = $this->telegramService->sendAbsenceAlert(
                    $student->fullname ?? $student->username ?? 'Unknown',
                    $student->username ?? (string) $student->id,
                    $student->class ?? 'Unknown Class',
                    $attendance->date ?? now()->format('Y-m-d'),
                    $session ? "{$session->start_time} - {$session->end_time}" : 'N/A'
                );

                if ($result['success']) {
                    $notification->update([
                        'status' => 'sent',
                        'telegram_message_id' => $result['message_id'],
                        'sent_at' => now(),
                    ]);
                    $results['succeeded']++;
                } else {
                    $notification->update(['error_message' => $result['error']]);
                    $results['failed']++;
                    $results['success'] = false;
                }
            } elseif ($notification->notification_type === 'education_api') {
                $result = $this->sendEducationDepartmentNotification($student, $session, $attendance);
                if ($result['success']) {
                    $results['succeeded']++;
                } else {
                    $results['failed']++;
                    $results['success'] = false;
                }
            }

            $results['retried']++;
        }

        return $results;
    }

    /**
     * Get notification status for a session
     */
    public function getSessionNotificationStatus(int $sessionId): array
    {
        $notifications = AbsenceNotification::where('session_id', $sessionId)
            ->select('id', 'student_id', 'notification_type', 'status', 'sent_at', 'error_message')
            ->get();

        $summary = AbsenceNotification::where('session_id', $sessionId)
            ->selectRaw(
                "COUNT(*) as total,
                 SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                 SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                 SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending"
            )
            ->first();

        return [
            'total' => (int) ($summary->total ?? 0),
            'sent' => (int) ($summary->sent ?? 0),
            'failed' => (int) ($summary->failed ?? 0),
            'pending' => (int) ($summary->pending ?? 0),
            'details' => $notifications->map(function ($n) {
                return [
                    'id' => $n->id,
                    'student_id' => $n->student_id,
                    'type' => $n->notification_type,
                    'status' => $n->status,
                    'sent_at' => $n->sent_at?->toIso8601String(),
                    'error' => $n->error_message,
                ];
            }),
        ];
    }
}

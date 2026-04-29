<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private ?string $botToken;
    private ?string $chatId;
    private bool $hasCredentials;

    public function __construct()
    {
        $this->botToken = $this->normalizeConfigValue(config('services.telegram.bot_token'));
        $this->chatId = $this->normalizeConfigValue(config('services.telegram.chat_id'));
        $this->hasCredentials = $this->botToken !== null && $this->chatId !== null;
    }

    /**
     * Check if Telegram is properly configured
     */
    public function isConfigured(): bool
    {
        return $this->hasCredentials && $this->isEnabled();
    }

    /**
     * Send a message to Telegram
     */
    public function sendMessage(string $message, ?string $parseMode = 'HTML'): array
    {
        Log::debug('TelegramService: Attempting to send message', [
            'botToken' => substr($this->botToken ?? '', 0, 5) . '...',
            'chatId' => $this->chatId,
            'isConfigured' => $this->isConfigured(),
        ]);

        if (!$this->isConfigured()) {
            Log::error('TelegramService: Configuration missing');
            return [
                'success' => false,
                'error' => 'Telegram is not configured. Please set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in .env',
            ];
        }

        try {
            $apiUrl = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            Log::debug('TelegramService: Calling API', ['url' => $apiUrl]);

            $response = Http::timeout(30)->post(
                $apiUrl,
                [
                    'chat_id' => $this->chatId,
                    'text' => $message,
                    'parse_mode' => $parseMode,
                ]
            );

            $result = $response->json();
            Log::debug('TelegramService: API Response', ['status' => $response->status(), 'body' => $result]);

            if ($response->successful() && ($result['ok'] ?? false)) {
                Log::info('Telegram notification sent successfully', [
                    'message_id' => $result['result']['message_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message_id' => $result['result']['message_id'] ?? null,
                ];
            }

            // Provide more helpful error messages for common issues
            $errorDescription = $result['description'] ?? 'Unknown error';
            $errorMessage = $errorDescription;
            
            // Map common errors to helpful messages
            if (str_contains($errorDescription, "Forbidden: bots can't send messages to bots")) {
                $errorMessage = "Cannot send to bot - TELEGRAM_CHAT_ID must be your personal user ID (e.g., 123456789), not the bot ID or username. Get your user ID at @userinfobot on Telegram.";
            } elseif (str_contains($errorDescription, "Forbidden: bot is not a member of the group")) {
                $errorMessage = "Bot is not a member of the group. Add the bot to your Telegram group first.";
            } elseif (str_contains($errorDescription, "Chat not found")) {
                $errorMessage = "Chat not found - Verify TELEGRAM_CHAT_ID is correct. For groups, use the negative group ID (e.g., -1001234567890).";
            } elseif ($response->status() === 401) {
                $errorMessage = "Invalid bot token - Verify TELEGRAM_BOT_TOKEN in .env is correct.";
            }

            Log::error('Telegram API error', [
                'error' => $errorDescription,
                'response' => $result,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('Telegram notification failed exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send an absence alert for a student
     */
    public function sendAbsenceAlert(
        string $studentName,
        string $studentId,
        string $className,
        string $date,
        string $sessionTime,
        ?int $attendanceRecordId = null
    ): array {
        $message = $this->formatAbsenceMessage($studentName, $studentId, $className, $date, $sessionTime);
        
        // Add inline keyboard with Reason button if attendanceRecordId is provided
        if ($attendanceRecordId) {
            $webAppUrl = config('services.web_app.url', env('WEB_APP_URL', 'http://localhost:5173'));
            $reasonUrl = $webAppUrl . '/education/absence-reason?record=' . $attendanceRecordId;
            
            // Try with button - fallback to message without button if HTTPS not available
            $result = $this->sendMessageWithInlineKeyboard($message, $reasonUrl);
            
            // If button fails due to HTTPS requirement, send without button
            if (!$result['success'] && strpos($result['error'] ?? '', 'HTTPS') !== false) {
                Log::warning('Telegram inline keyboard requires HTTPS, sending without button');
                return $this->sendMessage($message);
            }
            
            return $result;
        }
        
        return $this->sendMessage($message);
    }

    /**
     * Send a teacher attendance summary that lists late and absent students.
     *
     * @param array{
     *   teacher_name?: string|null,
     *   class_name?: string|null,
     *   session_name?: string|null,
     *   session_time?: string|null,
     *   date?: string|null,
     *   late_students?: array<int, array{name?: string|null, student_id?: string|null, class?: string|null}>,
     *   absent_students?: array<int, array{name?: string|null, student_id?: string|null, class?: string|null}>
     * } $summary
     */
    public function sendAttendanceSubmissionSummary(array $summary): array
    {
        return $this->sendMessage($this->formatAttendanceSubmissionSummary($summary));
    }

    /**
     * Send message with inline keyboard
     */
    public function sendMessageWithInlineKeyboard(string $message, string $reasonUrl): array
    {
        Log::debug('TelegramService: Attempting to send message with keyboard', [
            'chatId' => $this->chatId,
            'reasonUrl' => $reasonUrl
        ]);

        if (!$this->isConfigured()) {
            Log::error('TelegramService: Configuration missing');
            return [
                'success' => false,
                'error' => 'Telegram is not configured. Please set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in .env',
            ];
        }

        try {
            $apiUrl = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $response = Http::timeout(30)->post(
                $apiUrl,
                [
                    'chat_id' => $this->chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '📝 Add Reason / បន្ថែមហេតុផល',
                                    'url' => $reasonUrl
                                ]
                            ]
                        ]
                    ])
                ]
            );

            $result = $response->json();
            Log::debug('TelegramService: API Response (Keyboard)', ['status' => $response->status(), 'body' => $result]);

            if ($response->successful() && ($result['ok'] ?? false)) {
                Log::info('Telegram notification with keyboard sent successfully', [
                    'message_id' => $result['result']['message_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message_id' => $result['result']['message_id'] ?? null,
                ];
            }

            Log::error('Telegram API error (Keyboard)', [
                'error' => $result['description'] ?? 'Unknown error',
                'response' => $result,
            ]);

            return [
                'success' => false,
                'error' => $result['description'] ?? 'Failed to send message',
            ];
        } catch (\Exception $e) {
            Log::error('Telegram notification failed exception (Keyboard)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format the absence alert message
     */
    private function formatAbsenceMessage(
        string $studentName,
        string $studentId,
        string $className,
        string $date,
        string $sessionTime
    ): string {
        $emoji = "⚠️";
        $title = "ABSENCE ALERT";
        
        // Escape variables for HTML parse mode
        $studentName = htmlspecialchars($studentName);
        $studentId = htmlspecialchars($studentId);
        $className = htmlspecialchars($className);
        $date = htmlspecialchars($date);
        $sessionTime = htmlspecialchars($sessionTime);
        
        return <<<MESSAGE
{$emoji} <b>{$title}</b> {$emoji}

👤 <b>Student:</b> {$studentName}
🆔 <b>ID:</b> {$studentId}
🏫 <b>Class:</b> {$className}
📅 <b>Date:</b> {$date}
⏰ <b>Session:</b> {$sessionTime}

<i>Please contact the student's parent/guardian.</i>
MESSAGE;
    }

    /**
     * Format teacher attendance submission summary.
     */
    private function formatAttendanceSubmissionSummary(array $summary): string
    {
        $teacherName = htmlspecialchars((string) ($summary['teacher_name'] ?? 'Teacher'));
        $className = htmlspecialchars((string) ($summary['class_name'] ?? 'N/A'));
        $sessionName = htmlspecialchars((string) ($summary['session_name'] ?? 'N/A'));
        $sessionTime = htmlspecialchars((string) ($summary['session_time'] ?? 'N/A'));
        $date = htmlspecialchars((string) ($summary['date'] ?? 'N/A'));

        $lateStudents = $summary['late_students'] ?? [];
        $absentStudents = $summary['absent_students'] ?? [];

        $lines = [
            "━━━━━━━━━━━━━━━━━━",
            "📋 <b>ATTENDANCE REPORT</b>",
            "━━━━━━━━━━━━━━━━━━",
            "",
            "👨‍🏫 <b>Teacher:</b> {$teacherName}",
            "🏫 <b>Class:</b> {$className}",
            "⏰ <b>Session:</b> {$sessionName} ({$sessionTime})",
            "📅 <b>Date:</b> {$date}",
            "",
            "━━━━━━━━━━━━━━━━━━",
            "🟡 <b>LATE STUDENTS</b> (" . count($lateStudents) . ")",
            "━━━━━━━━━━━━━━━━━━",
            $this->formatAttendanceStudentListDetailed($lateStudents),
            "",
            "━━━━━━━━━━━━━━━━━━",
            "🔴 <b>ABSENT STUDENTS</b> (" . count($absentStudents) . ")",
            "━━━━━━━━━━━━━━━━━━",
            $this->formatAttendanceStudentListDetailed($absentStudents),
            "",
            "<i>Total: " . (count($lateStudents) + count($absentStudents)) . " students</i>",
        ];

        return implode("\n", $lines);
    }

    /**
     * Format student lines for attendance summaries with detailed info.
     *
     * @param array<int, array{name?: string|null, student_id?: string|null, class?: string|null}> $students
     */
    private function formatAttendanceStudentListDetailed(array $students): string
    {
        if (empty($students)) {
            return "• No students";
        }

        return collect($students)
            ->map(function (array $student): string {
                $name = htmlspecialchars((string) ($student['name'] ?? 'Unknown Student'));
                $studentId = htmlspecialchars((string) ($student['student_id'] ?? 'N/A'));
                $className = htmlspecialchars((string) ($student['class'] ?? 'N/A'));

                return "• {$name}\n  └─ ID: {$studentId} | Class: {$className}";
            })
            ->implode("\n");
    }

    /**
     * Send a test message to verify connection
     */
    public function sendTestMessage(): array
    {
        $message = "✅ <b>Test Message</b>\n\nTelegram notification service is working correctly!";
        return $this->sendMessage($message);
    }

    /**
     * Get bot info (for testing connection)
     */
    public function getBotInfo(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Telegram is not configured',
            ];
        }

        try {
            $response = Http::timeout(30)->get(
                "https://api.telegram.org/bot{$this->botToken}/getMe"
            );

            $result = $response->json();

            if ($response->successful() && ($result['ok'] ?? false)) {
                return [
                    'success' => true,
                    'bot_info' => $result['result'],
                ];
            }

            return [
                'success' => false,
                'error' => $result['description'] ?? 'Failed to get bot info',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function validateChatIdAgainstBot(array $botInfo): ?array
    {
        if (!$this->chatId) {
            return [
                'success' => false,
                'message' => 'Telegram chat ID is missing',
                'error' => 'Please set TELEGRAM_CHAT_ID in the backend .env file.',
            ];
        }

        $chatId = trim($this->chatId);

        if (str_starts_with($chatId, '@')) {
            return [
                'success' => false,
                'message' => 'Telegram chat ID must be numeric',
                'error' => 'Use a numeric personal, group, or channel chat ID instead of a username.',
            ];
        }

        if (!preg_match('/^-?\d+$/', $chatId)) {
            return [
                'success' => false,
                'message' => 'Telegram chat ID format is invalid',
                'error' => 'TELEGRAM_CHAT_ID must contain only digits, for example 123456789 or -1001234567890.',
            ];
        }

        $botId = isset($botInfo['id']) ? (string) $botInfo['id'] : null;

        if ($botId !== null && ltrim($chatId, '-') === $botId) {
            return [
                'success' => false,
                'message' => 'Invalid Telegram chat ID',
                'error' => 'TELEGRAM_CHAT_ID is using the bot ID. Replace it with your own user ID or a valid group/channel chat ID.',
            ];
        }

        return null;
    }

    private function isEnabled(): bool
    {
        return filter_var(config('services.telegram.enabled', true), FILTER_VALIDATE_BOOL);
    }

    private function normalizeConfigValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TimetableService
{
    protected string $baseUrl = 'https://timetables2.pnc.passerellesnumeriques.org/api/v1/google/events';

    /**
     * Fetch teacher schedule from external timetable API
     * 
     * @param string $calendarId The Google Calendar resource ID
     * @param string|null $date Date in Y-m-d format (defaults to today)
     * @return array
     */
    public function getTeacherSchedule(string $calendarId, ?string $date = null): array
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        // Apply date offset if configured (for testing with timetable data)
        // Set TIMETABLE_DATE_OFFSET in .env to adjust the date (e.g., -1 for last year)
        $dateOffset = (int) config('app.timetable_date_offset', 0);
        
        // Debug log the offset
        Log::info('TimetableService: Date offset from config: ' . $dateOffset);
        
        if ($dateOffset !== 0) {
            $date = $date->addYears($dateOffset);
        }

        // Set the date range to cover the entire day
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $startTimestamp = $startOfDay->timestamp;
        $endTimestamp = $endOfDay->timestamp;

        try {
            $response = Http::timeout(30)->get("{$this->baseUrl}/{$calendarId}", [
                'start' => $startTimestamp,
                'end' => $endTimestamp,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->processScheduleData($data, $date);
            }

            Log::error('Timetable API error: ' . $response->status() . ' - ' . $response->body());
            return $this->getEmptySchedule();
        } catch (\Exception $e) {
            Log::error('TimetableService error: ' . $e->getMessage());
            return $this->getEmptySchedule();
        }
    }

    /**
     * Process the raw schedule data into structured sessions
     * 
     * @param array $data Raw API response
     * @param Carbon $date The date for the schedule
     * @return array
     */
    protected function processScheduleData(array $data, Carbon $date): array
    {
        $events = $data['items'] ?? [];

        if (empty($events)) {
            return $this->getEmptySchedule();
        }

        // Sort events by start time
        usort($events, function ($a, $b) {
            $startA = $a['start']['dateTime'] ?? $a['start']['date'] ?? '';
            $startB = $b['start']['dateTime'] ?? $b['start']['date'] ?? '';
            return strcmp($startA, $startB);
        });

        // Extract sessions (up to 4 sessions for the dashboard)
        $sessions = [];
        $sessionNumber = 1;

        foreach ($events as $event) {
            if ($sessionNumber > 4) break;

            $session = $this->parseEventToSession($event, $sessionNumber);
            if ($session) {
                $sessions[] = $session;
                $sessionNumber++;
            }
        }

        return [
            'date' => $date->toDateString(),
            'sessions' => $sessions,
            'total_sessions' => count($sessions),
        ];
    }

    /**
     * Parse a Google Calendar event into our session format
     * 
     * @param array $event Google Calendar event
     * @param int $sessionNumber Session number (1-4)
     * @return array|null
     */
    protected function parseEventToSession(array $event, int $sessionNumber): ?array
    {
        $startDateTime = $event['start']['dateTime'] ?? null;
        $endDateTime = $event['end']['dateTime'] ?? null;

        if (!$startDateTime) {
            return null;
        }

        $startTime = Carbon::parse($startDateTime)->format('H:i');
        $endTime = $endDateTime ? Carbon::parse($endDateTime)->format('H:i') : '';

        // Extract summary (subject, room, class)
        $summary = $event['summary'] ?? '';
        $description = $event['description'] ?? '';

        // Parse the summary to extract subject, room, and class
        // Expected format: "Subject - Room - Class" or similar
        $parsed = $this->parseEventSummary($summary, $description);

        return [
            'session_number' => $sessionNumber,
            'session_name' => $this->getSessionName($sessionNumber),
            'subject' => $parsed['subject'],
            'room' => $parsed['room'],
            'class' => $parsed['class'],
            'academic_year' => $parsed['academic_year'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'event_summary' => $summary,
        ];
    }

    /**
     * Parse event summary to extract subject, room, and class info
     * 
     * @param string $summary Event summary
     * @param string $description Event description
     * @return array
     */
    protected function parseEventSummary(string $summary, string $description): array
    {
        $result = [
            'subject' => $summary,
            'room' => '',
            'class' => '',
            'academic_year' => '',
        ];

        // Try to parse the summary
        // Common patterns:
        // - "Math - Room 101 - Y1-2027D"
        // - "English @ Room 305"
        // - "Science - Y2-2026"

        // Remove common prefixes/suffixes
        $cleanSummary = trim($summary);

        // Try to extract room (patterns like "Room 101", "@ 305", "R301")
        if (preg_match('/(?:Room|Room\s*#|@|R\d{3})[\s:]*(\w+)/i', $cleanSummary, $matches)) {
            $result['room'] = $matches[1];
        } elseif (preg_match('/@\s*(\w+)/', $cleanSummary, $matches)) {
            $result['room'] = $matches[1];
        }

        // Try to extract academic year/class (patterns like "Y1-2027D", "Y2-2026", "Grade 10")
        if (preg_match('/(Y\d+-\d{4}[A-Z]?|Grade\s*\d+|G\d+)/i', $cleanSummary, $matches)) {
            $result['academic_year'] = $matches[1];
            $result['class'] = $matches[1];
        }

        // Extract subject name by removing room and class info
        $subject = $cleanSummary;
        $subject = preg_replace('/(?:Room|Room\s*#|@|R\d{3})[\s:]*\w+/i', '', $subject);
        $subject = preg_replace('/(Y\d+-\d{4}[A-Z]?|Grade\s*\d+|G\d+)/i', '', $subject);
        $subject = preg_replace('/[-\@]+/', ' ', $subject);
        $subject = trim($subject);

        if (!empty($subject)) {
            $result['subject'] = $subject;
        }

        // If no academic year found in summary, try description
        if (empty($result['academic_year']) && !empty($description)) {
            if (preg_match('/(Y\d+-\d{4}[A-Z]?|Grade\s*\d+)/i', $description, $matches)) {
                $result['academic_year'] = $matches[1];
                $result['class'] = $matches[1];
            }
        }

        return $result;
    }

    /**
     * Get session name based on session number
     * 
     * @param int $number
     * @return string
     */
    protected function getSessionName(int $number): string
    {
        return match ($number) {
            1 => 'First Session',
            2 => 'Second Session',
            3 => 'Third Session',
            4 => 'Fourth Session',
            default => "Session {$number}",
        };
    }

    /**
     * Return empty schedule structure
     * 
     * @return array
     */
    protected function getEmptySchedule(): array
    {
        return [
            'date' => Carbon::today()->toDateString(),
            'sessions' => [],
            'total_sessions' => 0,
        ];
    }

    /**
     * Get list of teacher calendar IDs
     * This maps teacher names to their Google Calendar resource IDs
     * Retrieved from timetables2.pnc.passerellesnumeriques.org/api/v1/calendar/teachers
     * 
     * @return array
     */
    public function getTeacherCalendars(): array
    {
        return [
            'Davy' => 'passerellesnumeriques.org_353233363037333530@resource.calendar.google.com',
            'Him' => 'passerellesnumeriques.org_343437393530363136@resource.calendar.google.com',
            'Lavy' => 'passerellesnumeriques.org_2d3331373838323735363330@resource.calendar.google.com',
            'Mengheang' => 'c_1886h9lqonri4ig0noe2vrfvp8fb8@resource.calendar.google.com',
            'Ouchi' => 'c_188b20cg9s5uoh12jobk987cfbh2g@resource.calendar.google.com',
            'Puthy' => 'passerellesnumeriques.org_3733323437383733383932@resource.calendar.google.com',
            'Rady' => 'passerellesnumeriques.org_2d3132393337373934393735@resource.calendar.google.com',
            'Savoeurn' => 'passerellesnumeriques.org_3539373731343733353932@resource.calendar.google.com',
            'Sim' => 'passerellesnumeriques.org_3635313135323433383533@resource.calendar.google.com',
            'Sokhom' => 'passerellesnumeriques.org_2d3633393338303431343434@resource.calendar.google.com',
            'Somkhan' => 'passerellesnumeriques.org_3933333731393139373031@resource.calendar.google.com',
            'Sovanchansreyleap' => 'c_1884lpdesdih0irbl36ss1j7vt7aq@resource.calendar.google.com',
            'Vandy' => 'passerellesnumeriques.org_3331363536313232373638@resource.calendar.google.com',
            'Yon' => 'c_1882ckecmfgb0h7fmha0u9t3rbd5g@resource.calendar.google.com',
        ];
    }

    /**
     * Get calendar ID for a specific teacher by name
     * 
     * @param string $teacherName The teacher's name
     * @return string|null
     */
    public function getCalendarIdByTeacherName(string $teacherName): ?string
    {
        $calendars = $this->getTeacherCalendars();

        // Try exact match first
        if (isset($calendars[$teacherName])) {
            return $calendars[$teacherName];
        }

        // Try case-insensitive match
        $teacherNameLower = strtolower($teacherName);
        foreach ($calendars as $name => $calendarId) {
            if (strtolower($name) === $teacherNameLower) {
                return $calendarId;
            }
        }

        // Try partial match (e.g., "Sovanchansreyleap " with trailing space)
        foreach ($calendars as $name => $calendarId) {
            if (strtolower(trim($name)) === $teacherNameLower) {
                return $calendarId;
            }
        }

        return null;
    }
}

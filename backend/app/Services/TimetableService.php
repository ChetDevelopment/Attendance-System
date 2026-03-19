<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Session;
use App\Models\SchoolClass;
use App\Models\User;

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

            // The API returns data directly as an array, not {items: [...]}
            $data = $response->json();

            // Check if we got valid data (array with events)
            if (is_array($data) && !empty($data)) {
                Log::info('TimetableService: Received ' . count($data) . ' events from external API');
                $scheduleData = $this->processScheduleData($data, $date);

                // If external API returns sessions, use them
                if (!empty($scheduleData['sessions'])) {
                    Log::info('TimetableService: Using external API data, found ' . count($scheduleData['sessions']) . ' sessions');
                    return $scheduleData;
                }

                Log::info('TimetableService: External API returned empty sessions, trying fallback');
            } else {
                Log::info('TimetableService: External API returned empty data, trying fallback');
            }
        } catch (\Exception $e) {
            Log::error('TimetableService error: ' . $e->getMessage());
        }

        // Fallback: Try fetching from local database if external API returns empty
        // Use actual current date for local data, not the offset-adjusted date
        $localDate = Carbon::today();
        Log::info('TimetableService: Falling back to local database for date: ' . $localDate->toDateString());
        return $this->getLocalSchedule($calendarId, $localDate);
    }

    /**
     * Get schedule from local database (fallback when external API has no data)
     * 
     * @param string $calendarId The Google Calendar resource ID (used to find teacher)
     * @param Carbon $date The date to fetch schedule for
     * @return array
     */
    protected function getLocalSchedule(string $calendarId, Carbon $date): array
    {
        // Find teacher by calendar_id
        Log::info('getLocalSchedule: Looking for teacher with calendar_id: ' . $calendarId);

        $teacher = User::where('calendar_id', $calendarId)->first();

        // If not found, try finding by name from the teacher calendars mapping
        if (!$teacher) {
            $teacherName = $this->getTeacherNameFromCalendarId($calendarId);
            if ($teacherName) {
                $teacher = User::where('name', $teacherName)->first();
            }
        }

        if (!$teacher) {
            Log::warning('getLocalSchedule: No teacher found for calendar_id: ' . $calendarId);
            return $this->getEmptySchedule($date);
        }

        Log::info('getLocalSchedule: Found teacher: ' . $teacher->name . ' (id: ' . $teacher->id . ')');

        // Get all active sessions for today that this teacher teaches
        $sessions = Session::where('is_active', true)
            ->orderBy('start_time')
            ->get();
        Log::info('getLocalSchedule: Found ' . $sessions->count() . ' sessions');

        $teacherClasses = $this->getTeacherClasses($teacher, $date);
        $teacherClassIds = $teacherClasses->pluck('id')->values();
        Log::info('getLocalSchedule: Teacher ' . $teacher->name . ' resolved class IDs: ' . json_encode($teacherClassIds));

        if ($teacherClassIds->isEmpty()) {
            Log::warning('getLocalSchedule: No classes found for teacher_id: ' . $teacher->id);
            return $this->getEmptySchedule($date);
        }

        $todayAttendances = Attendance::whereDate('date', $date->toDateString())
            ->whereIn('class_id', $teacherClassIds)
            ->with(['class', 'session'])
            ->get()
            ->groupBy('session_id');

        $recentSessionTemplates = $this->getRecentSessionTemplates($teacher, $teacherClassIds->all(), $date);

        $scheduleSessions = [];
        $sessionNumber = 1;

        foreach ($sessions as $session) {
            if ($sessionNumber > 4) {
                break;
            }

            $attendance = $todayAttendances->get($session->id)?->first();
            if ($attendance && $attendance->class) {
                $scheduleSessions[] = $this->buildLocalSession(
                    $attendance->class,
                    $session,
                    $sessionNumber,
                    ['is_local' => true]
                );
                $sessionNumber++;
                continue;
            }

            $template = $recentSessionTemplates->get($session->id);
            if ($template && $template->class) {
                $scheduleSessions[] = $this->buildLocalSession(
                    $template->class,
                    $session,
                    $sessionNumber,
                    ['is_local' => true, 'is_inferred' => true]
                );
                $sessionNumber++;
                continue;
            }

            $fallbackClass = $teacherClasses->get(($sessionNumber - 1) % max($teacherClasses->count(), 1));
            if ($fallbackClass) {
                $scheduleSessions[] = $this->buildLocalSession(
                    $fallbackClass,
                    $session,
                    $sessionNumber,
                    ['is_local' => true, 'is_placeholder' => true]
                );
                $sessionNumber++;
            }
        }

        return [
            'date' => $date->toDateString(),
            'sessions' => $scheduleSessions,
            'total_sessions' => count($scheduleSessions),
            'teacher_name' => $teacher->name,
            'source' => 'local',
        ];
    }

    /**
     * Resolve the classes a teacher is currently teaching.
     *
     * We prefer explicit class assignments, but also infer classes from recent
     * attendance submissions because some local data sets are missing teacher_id.
     */
    protected function getTeacherClasses(User $teacher, Carbon $date)
    {
        $directClassIds = SchoolClass::where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->pluck('id');

        $attendanceClassIds = Attendance::where('submitted_by', $teacher->id)
            ->whereDate('date', '<=', $date->toDateString())
            ->orderByDesc('date')
            ->limit(20)
            ->pluck('class_id');

        $classIds = $directClassIds
            ->merge($attendanceClassIds)
            ->filter()
            ->unique()
            ->values();

        if ($classIds->isEmpty()) {
            return collect();
        }

        return SchoolClass::whereIn('id', $classIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->values();
    }

    /**
     * Use recent attendance history to infer which class belongs to each session.
     */
    protected function getRecentSessionTemplates(User $teacher, array $classIds, Carbon $date)
    {
        if (empty($classIds)) {
            return collect();
        }

        $attendances = Attendance::where('submitted_by', $teacher->id)
            ->whereIn('class_id', $classIds)
            ->whereDate('date', '<=', $date->toDateString())
            ->with(['class', 'session'])
            ->orderByDesc('date')
            ->get();

        return $attendances
            ->filter(fn($attendance) => $attendance->session_id && $attendance->class)
            ->unique('session_id')
            ->keyBy('session_id');
    }

    /**
     * Build a schedule card entry from local class/session data.
     */
    protected function buildLocalSession(SchoolClass $class, Session $session, int $sessionNumber, array $flags = []): array
    {
        return array_merge([
            'session_number' => $sessionNumber,
            'session_name' => $this->getSessionName($sessionNumber),
            'subject' => $class->name,
            'room' => 'TBD',
            'class' => $class->code,
            'academic_year' => $class->code,
            'start_time' => Carbon::parse($session->start_time)->format('H:i'),
            'end_time' => Carbon::parse($session->end_time)->format('H:i'),
            'event_summary' => $class->name . ' - ' . $class->code,
        ], $flags);
    }

    /**
     * Get teacher name from calendar ID by reversing the mapping
     * 
     * @param string $calendarId
     * @return string|null
     */
    protected function getTeacherNameFromCalendarId(string $calendarId): ?string
    {
        $calendars = $this->getTeacherCalendars();
        foreach ($calendars as $name => $id) {
            if ($id === $calendarId || strpos($calendarId, $id) !== false) {
                return $name;
            }
        }
        return null;
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
        // Handle both formats: {items: [...]} or directly [...]
        // Also handle Google Calendar format {start: {dateTime: ...}} and
        // timetable API format {start: "..."}
        $events = $data['items'] ?? $data;

        if (empty($events)) {
            return $this->getEmptySchedule();
        }

        // Sort events by start time - handle both formats
        usort($events, function ($a, $b) {
            $startA = $a['start']['dateTime'] ?? $a['start'] ?? $a['start']['date'] ?? '';
            $startB = $b['start']['dateTime'] ?? $b['start'] ?? $b['start']['date'] ?? '';
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
        // Handle both Google Calendar format {start: {dateTime: ...}} and 
        // timetable API format {start: "..."}
        $startDateTime = $event['start']['dateTime'] ?? $event['start'] ?? null;
        $endDateTime = $event['end']['dateTime'] ?? $event['end'] ?? null;

        if (!$startDateTime) {
            return null;
        }

        $startTime = Carbon::parse($startDateTime)->format('H:i');
        $endTime = $endDateTime ? Carbon::parse($endDateTime)->format('H:i') : '';

        // Extract summary (subject, room, class) - handle both "title" and "summary"
        $summary = $event['title'] ?? $event['summary'] ?? '';
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
        // Common patterns from timetable API:
        // - "General English, B12, Lavy, Y2-2026 A" (Subject, Room, Teacher, Class)
        // - "General English, Lavy, Y2-2026 A" (Subject, Teacher, Class) - no room
        // - "Learning Lab, Teacher, Class" (Room as first item)
        // - "Math - Room 101 - Y1-2027D"

        // Remove trailing commas and extra spaces
        $cleanSummary = trim($summary, ', ');

        // Try to extract room first
        // Pattern 1: Room like B12, B23 (letter + 2-3 digits, appears after subject)
        if (preg_match('/,\s*([A-Z]\d{2,3}),/', $cleanSummary, $matches)) {
            $result['room'] = $matches[1];
            $cleanSummary = str_replace(', ' . $matches[1] . ',', ',', $cleanSummary);
        }
        // Pattern 2: Room with parentheses like "A21 (60)" or "Learning Lab"
        elseif (preg_match('/,\s*([A-Z]\d{1,2}\s*\(\d+\)),/i', $cleanSummary, $matches)) {
            $result['room'] = $matches[1];
            $cleanSummary = str_replace(', ' . $matches[1] . ',', ',', $cleanSummary);
        }
        // Pattern 3: Room name like "Learning Lab" or "Computer Lab" (at the beginning)
        elseif (preg_match('/^([A-Za-z\s]+),\s*[A-Z]/', $cleanSummary, $matches)) {
            $roomCandidate = trim($matches[1]);
            // Check if it looks like a room name
            if (preg_match('/(Lab|Room|Office|Hall|Theater|Theatre)$/i', $roomCandidate)) {
                $result['room'] = $roomCandidate;
                $cleanSummary = preg_replace('/^' . preg_quote($roomCandidate, '/') . ',\s*/', '', $cleanSummary);
            }
        }
        // Pattern 4: Room after "Room" keyword
        elseif (preg_match('/(?:Room|Room\s*#|@|R\d{3})[\s:]*(\w+)/i', $cleanSummary, $matches)) {
            $result['room'] = $matches[1];
        }

        // Try to extract academic year/class (patterns like "Y2-2026 A", "Y1-2027D")
        // This pattern looks for Y followed by number, dash, year, optional space and letter
        if (preg_match('/,\s*(Y\d+-\d{4}[A-Z]?)\s*$/', $cleanSummary, $matches)) {
            $result['academic_year'] = $matches[1];
            $result['class'] = $matches[1];
            $cleanSummary = preg_replace('/,\s*Y\d+-\d{4}[A-Z]?\s*$/', '', $cleanSummary);
        } elseif (preg_match('/(Y\d+-\d{4}[A-Z]?|Grade\s*\d+)/i', $cleanSummary, $matches)) {
            $result['academic_year'] = $matches[1];
            $result['class'] = $matches[1];
        }

        // Remove teacher names from the summary
        $teacherNames = ['Lavy', 'Davy', 'Him', 'Mengheang', 'Ouchi', 'Puthy', 'Rady', 'Savoeurn', 'Sim', 'Sokhom', 'Somkhan', 'Sovanchansreyleap', 'Vandy', 'Yon'];
        foreach ($teacherNames as $teacherName) {
            $cleanSummary = preg_replace('/,\s*' . $teacherName . '\s*,?/i', ',', $cleanSummary);
        }

        // Extract subject name by removing room and class info
        $subject = $cleanSummary;
        $subject = preg_replace('/(?:Room|Room\s*#|@|R\d{3})[\s:]*\w+/i', '', $subject);
        $subject = preg_replace('/(Y\d+-\d{4}[A-Z]?|Grade\s*\d+)/i', '', $subject);
        $subject = preg_replace('/[-\@,]+/', ' ', $subject);
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
     * @param Carbon|null $date The date for the schedule
     * @return array
     */
    public function getEmptySchedule(?Carbon $date = null): array
    {
        return [
            'date' => ($date ? $date->toDateString() : Carbon::today()->toDateString()),
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

<?php

namespace App\Services;

use App\Models\Session;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SessionService
{
    /**
     * Cache key prefix
     */
    const CACHE_KEY = 'sessions.active.v2';
    
    /**
     * Redis service instance
     */
    protected ?RedisService $redisService = null;

    /**
     * Get Redis service instance
     */
    protected function getRedisService(): ?RedisService
    {
        if ($this->redisService === null) {
            try {
                $this->redisService = new RedisService();
                if (!$this->redisService->isAvailable()) {
                    $this->redisService = null;
                }
            } catch (\Exception $e) {
                Log::warning('Redis service not available', ['error' => $e->getMessage()]);
                $this->redisService = null;
            }
        }
        return $this->redisService;
    }

    /**
     * Get all active sessions
     */
    public function getActiveSessions()
    {
        $cacheEnabled = config('sessions.cache.enabled', true);
        $cacheTtl = config('sessions.cache.ttl', 300);
        
        if ($cacheEnabled) {
            return Cache::remember(self::CACHE_KEY, $cacheTtl, function () {
                return Session::active()
                    ->ordered()
                    ->get();
            });
        }
        
        return Session::active()
            ->ordered()
            ->get();
    }

    /**
     * Get session configuration info
     */
    public function getSessionConfig(): array
    {
        return [
            'sessions_per_day' => config('sessions.sessions_per_day', 3),
            'active_sessions_count' => Session::active()->count(),
            'timezone' => config('sessions.timezone', 'Asia/Bangkok'),
            'default_late_threshold' => config('sessions.default_late_threshold', 15),
        ];
    }

    /**
     * Get current active session based on current time
     */
    public function getCurrentSession($time = null)
    {
        $checkTime = $time instanceof Carbon ? $time : Carbon::now(config('sessions.timezone', 'Asia/Bangkok'));
        
        $sessions = $this->getActiveSessions();
        
        foreach ($sessions as $session) {
            if ($session->isTimeWithinSession($checkTime)) {
                return $session;
            }
        }
        
        return null;
    }

    /**
     * Get next upcoming session
     */
    public function getNextSession($time = null)
    {
        $checkTime = $time instanceof Carbon ? $time : Carbon::now(config('sessions.timezone', 'Asia/Bangkok'));
        
        $sessions = $this->getActiveSessions()
            ->filter(function ($session) use ($checkTime) {
                $sessionStart = Carbon::parse($session->start_time, config('sessions.timezone', 'Asia/Bangkok'));
                return $sessionStart->greaterThan($checkTime);
            });
        
        if ($sessions->isEmpty()) {
            return null;
        }
        
        return $sessions->sortBy(function ($session) {
            return $session->start_time;
        })->first();
    }

    /**
     * Check if a specific session is currently active
     */
    public function isSessionActive($sessionId): bool
    {
        // Try Redis cache first
        $redisService = $this->getRedisService();
        if ($redisService) {
            $cachedSession = $redisService->getCachedSession($sessionId);
            if ($cachedSession !== null) {
                return $cachedSession['is_active'] ?? false;
            }
        }
        
        $session = Session::find($sessionId);
        
        if (!$session) {
            return false;
        }
        
        // Cache the session status
        if ($redisService) {
            $redisService->cacheSession($sessionId, [
                'id' => $session->id,
                'name' => $session->name,
                'is_active' => $session->is_active,
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
                'date' => $session->date,
                'late_threshold' => $session->late_threshold,
            ]);
        }
        
        return $session->isCurrentlyActive();
    }

    /**
     * Check if student can check in for a specific session
     */
    public function canCheckIn($studentId, $sessionId): array
    {
        // Try Redis cache first
        $redisService = $this->getRedisService();
        if ($redisService) {
            // Check if student already checked in using Redis (prevent duplicate check-ins)
            if ($redisService->hasCheckedIn($studentId, $sessionId)) {
                return [
                    'allowed' => false,
                    'reason' => 'Already checked in for this session',
                    'duplicate' => true,
                ];
            }
            
            // Try to record check-in atomically
            $recorded = $redisService->trackCheckIn($studentId, $sessionId);
            if (!$recorded) {
                return [
                    'allowed' => false,
                    'reason' => 'Already checked in for this session',
                    'duplicate' => true,
                ];
            }
        }
        
        // Get session (try cache first)
        $session = null;
        if ($redisService) {
            $cachedData = $redisService->getCachedSession($sessionId);
            if ($cachedData) {
                // Create a temporary session-like object from cache
                $sessionData = $cachedData;
            }
        }
        
        if (!$session) {
            $session = Session::find($sessionId);
        }
        
        if (!$session) {
            // Rollback Redis check-in if we recorded it
            if ($redisService) {
                $redisService->removeCheckIn($studentId, $sessionId);
            }
            return [
                'allowed' => false,
                'reason' => 'Session not found',
            ];
        }
        
        if (!($session->is_active ?? false)) {
            // Rollback Redis check-in
            if ($redisService) {
                $redisService->removeCheckIn($studentId, $sessionId);
            }
            return [
                'allowed' => false,
                'reason' => 'Session is not active',
            ];
        }
        
        $now = Carbon::now(config('sessions.timezone', 'Asia/Bangkok'));
        
        // Check if within session time
        if (!$session->isTimeWithinSession($now)) {
            // Check if early check-in is allowed
            $earlyCheckinMinutes = config('sessions.validation.early_checkin_minutes', 30);
            $sessionStart = Carbon::parse($session->start_time, config('sessions.timezone', 'Asia/Bangkok'));
            $earlyThreshold = $sessionStart->subMinutes($earlyCheckinMinutes);
            
            if ($now->greaterThan($earlyThreshold) && $now->lessThan($sessionStart)) {
                return [
                    'allowed' => true,
                    'reason' => 'Early check-in allowed',
                    'is_early' => true,
                ];
            }
            
            // Rollback Redis check-in
            if ($redisService) {
                $redisService->removeCheckIn($studentId, $sessionId);
            }
            
            return [
                'allowed' => false,
                'reason' => 'Session is not currently active',
            ];
        }
        
        return [
            'allowed' => true,
            'reason' => 'Check-in allowed',
        ];
    }

    /**
     * Check if check-in time is late for a session
     */
    public function isLate($sessionId, $checkInTime = null): array
    {
        $session = Session::find($sessionId);
        
        if (!$session) {
            return [
                'is_late' => false,
                'minutes_late' => 0,
                'reason' => 'Session not found',
            ];
        }
        
        $time = $checkInTime instanceof Carbon 
            ? $checkInTime 
            : Carbon::now(config('sessions.timezone', 'Asia/Bangkok'));
        
        $isLate = $session->isLate($time);
        $minutesLate = $session->getMinutesLate($time);
        
        return [
            'is_late' => $isLate,
            'minutes_late' => $minutesLate,
            'late_threshold' => $session->late_threshold,
            'reason' => $isLate 
                ? "Check-in is {$minutesLate} minutes late (threshold: {$session->late_threshold} minutes)"
                : 'On time',
        ];
    }

    /**
     * Get session by ID
     */
    public function getSessionById($sessionId): ?Session
    {
        return Session::find($sessionId);
    }

    /**
     * Get session by order number
     */
    public function getSessionByOrder($order): ?Session
    {
        return Session::where('order', $order)->active()->first();
    }

    /**
     * Get all sessions (including inactive)
     */
    public function getAllSessions()
    {
        return Session::ordered()->get();
    }

    /**
     * Create a new session
     */
    public function createSession(array $data): Session
    {
        return Session::create($data);
    }

    /**
     * Update an existing session
     */
    public function updateSession($sessionId, array $data): ?Session
    {
        $session = Session::find($sessionId);
        
        if (!$session) {
            return null;
        }
        
        $session->update($data);
        $this->clearCache();
        
        // Clear Redis cache for this session
        $redisService = $this->getRedisService();
        if ($redisService) {
            $redisService->clearSessionCheckIns($sessionId);
        }
        
        return $session->fresh();
    }

    /**
     * Delete a session
     */
    public function deleteSession($sessionId): bool
    {
        $session = Session::find($sessionId);
        
        if (!$session) {
            return false;
        }
        
        $session->delete();
        $this->clearCache();
        
        // Clear Redis cache for this session
        $redisService = $this->getRedisService();
        if ($redisService) {
            $redisService->clearSessionCheckIns($sessionId);
        }
        
        return true;
    }

    /**
     * Toggle session active status
     */
    public function toggleSessionStatus($sessionId): ?Session
    {
        $session = Session::find($sessionId);
        
        if (!$session) {
            return null;
        }
        
        $session->update(['is_active' => !$session->is_active]);
        $this->clearCache();
        
        // Clear Redis cache for this session
        $redisService = $this->getRedisService();
        if ($redisService) {
            $redisService->clearSessionCheckIns($sessionId);
        }
        
        return $session->fresh();
    }

    /**
     * Initialize default PNC sessions
     */
    public function initializeDefaultSessions(): int
    {
        $defaultSessions = config('sessions.default_sessions');
        $created = 0;
        
        foreach ($defaultSessions as $sessionData) {
            // Check if session with same order already exists
            $exists = Session::where('order', $sessionData['order'])->exists();
            
            if (!$exists) {
                Session::create($sessionData);
                $created++;
            }
        }
        
        $this->clearCache();
        
        return $created;
    }

    /**
     * Check if sessions are initialized
     */
    public function areSessionsInitialized(): bool
    {
        return Session::count() > 0;
    }

    /**
     * Get attendance status for a student in a session
     */
    public function getStudentAttendanceStatus($studentId, $sessionId): ?array
    {
        $attendance = AttendanceRecord::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->first();
        
        if (!$attendance) {
            return null;
        }
        
        return [
            'checked_in' => true,
            'check_in_time' => $attendance->check_in_time,
            'status' => $attendance->status,
            'is_late' => $attendance->is_late ?? false,
        ];
    }

    /**
     * Clear session cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get sessions for a specific date
     */
    public function getSessionsForDate($date): \Illuminate\Database\Eloquent\Collection
    {
        return Session::where('date', $date)
            ->orWhereNull('date')
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Validate session time window
     */
    public function validateSessionTime($sessionId): array
    {
        $session = Session::find($sessionId);
        
        if (!$session) {
            return [
                'valid' => false,
                'reason' => 'Session not found',
            ];
        }
        
        $now = Carbon::now(config('sessions.timezone', 'Asia/Bangkok'));
        
        if (!$session->is_active) {
            return [
                'valid' => false,
                'reason' => 'Session is not active',
            ];
        }
        
        if (!$session->isTimeWithinSession($now)) {
            return [
                'valid' => false,
                'reason' => 'Current time is outside session hours',
                'session_start' => $session->start_time,
                'session_end' => $session->end_time,
                'current_time' => $now->format('H:i'),
            ];
        }
        
        return [
            'valid' => true,
            'reason' => 'Valid session time',
            'session' => $session,
        ];
    }

    /**
     * Get active check-in count for a session from Redis
     */
    public function getActiveCheckInCount($sessionId): int
    {
        $redisService = $this->getRedisService();
        if ($redisService) {
            return $redisService->getActiveCheckInCount($sessionId);
        }
        return 0;
    }

    /**
     * Get all active check-ins for a session from Redis
     */
    public function getActiveCheckIns($sessionId): array
    {
        $redisService = $this->getRedisService();
        if ($redisService) {
            return $redisService->getActiveCheckIns($sessionId);
        }
        return [];
    }
}

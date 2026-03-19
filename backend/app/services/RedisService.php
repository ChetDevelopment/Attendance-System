<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * RedisService - Handles Redis operations for caching, real-time validation, and queue processing
 */
class RedisService
{
    /**
     * Cache TTL constants (in seconds)
     */
    const CACHE_TTL_SESSION = 600; // 10 minutes
    const CACHE_TTL_SESSION_CONFIG = 900; // 15 minutes
    const CHECKIN_TTL = 3600; // 1 hour for check-in tracking

    /**
     * Key prefixes
     */
    const PREFIX_SESSION = 'session:';
    const PREFIX_CHECKIN = 'checkin:';
    const PREFIX_ACTIVE = 'active:';

    /**
     * Cache session data
     *
     * @param int $sessionId
     * @param array $data
     * @param int|null $ttl
     * @return bool
     */
    public function cacheSession(int $sessionId, array $data, ?int $ttl = null): bool
    {
        try {
            $key = $this->getSessionKey($sessionId);
            $ttl = $ttl ?? self::CACHE_TTL_SESSION;
            
            Redis::setex($key, $ttl, json_encode($data));
            
            Log::debug('Session cached', ['session_id' => $sessionId, 'ttl' => $ttl]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cache session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get cached session data
     *
     * @param int $sessionId
     * @return array|null
     */
    public function getCachedSession(int $sessionId): ?array
    {
        try {
            $key = $this->getSessionKey($sessionId);
            $data = Redis::get($key);
            
            if ($data) {
                Log::debug('Session cache hit', ['session_id' => $sessionId]);
                return json_decode($data, true);
            }
            
            Log::debug('Session cache miss', ['session_id' => $sessionId]);
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get cached session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Track a real-time check-in using Redis atomic operations
     * Uses SETNX to prevent duplicate check-ins
     *
     * @param int $studentId
     * @param int $sessionId
     * @return bool True if check-in was recorded (not duplicate)
     */
    public function trackCheckIn(int $studentId, int $sessionId): bool
    {
        try {
            $key = $this->getCheckInKey($sessionId, $studentId);
            
            // Use SETNX (SET if Not eXists) for atomic operation
            // This prevents race conditions and duplicate check-ins
            $result = Redis::setnx($key, json_encode([
                'student_id' => $studentId,
                'session_id' => $sessionId,
                'checked_in_at' => now()->toIso8601String(),
            ]));
            
            if ($result) {
                // Set TTL for auto-cleanup
                Redis::expire($key, self::CHECKIN_TTL);
                
                // Add to active check-ins set for the session
                $activeKey = $this->getActiveCheckInsKey($sessionId);
                Redis::sadd($activeKey, $studentId);
                Redis::expire($activeKey, self::CHECKIN_TTL);
                
                Log::debug('Check-in tracked', [
                    'student_id' => $studentId,
                    'session_id' => $sessionId,
                ]);
                return true;
            }
            
            Log::debug('Duplicate check-in prevented', [
                'student_id' => $studentId,
                'session_id' => $sessionId,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to track check-in', [
                'student_id' => $studentId,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            // On error, allow check-in (fail-open for better UX)
            return true;
        }
    }

    /**
     * Check if student already checked in for a session
     *
     * @param int $studentId
     * @param int $sessionId
     * @return bool
     */
    public function hasCheckedIn(int $studentId, int $sessionId): bool
    {
        try {
            $key = $this->getCheckInKey($sessionId, $studentId);
            return Redis::exists($key) > 0;
        } catch (\Exception $e) {
            Log::error('Failed to check if student checked in', [
                'student_id' => $studentId,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get all active check-ins for a session
     *
     * @param int $sessionId
     * @return array
     */
    public function getActiveCheckIns(int $sessionId): array
    {
        try {
            $activeKey = $this->getActiveCheckInsKey($sessionId);
            $studentIds = Redis::smembers($activeKey);
            
            $checkIns = [];
            foreach ($studentIds as $studentId) {
                $checkInKey = $this->getCheckInKey($sessionId, $studentId);
                $data = Redis::get($checkInKey);
                if ($data) {
                    $checkIns[] = json_decode($data, true);
                }
            }
            
            return $checkIns;
        } catch (\Exception $e) {
            Log::error('Failed to get active check-ins', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get count of active check-ins for a session
     *
     * @param int $sessionId
     * @return int
     */
    public function getActiveCheckInCount(int $sessionId): int
    {
        try {
            $activeKey = $this->getActiveCheckInsKey($sessionId);
            return (int) Redis::scard($activeKey);
        } catch (\Exception $e) {
            Log::error('Failed to get active check-in count', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Remove check-in record (for rollback scenarios)
     *
     * @param int $studentId
     * @param int $sessionId
     * @return bool
     */
    public function removeCheckIn(int $studentId, int $sessionId): bool
    {
        try {
            $key = $this->getCheckInKey($sessionId, $studentId);
            Redis::del($key);
            
            $activeKey = $this->getActiveCheckInsKey($sessionId);
            Redis::srem($activeKey, $studentId);
            
            Log::debug('Check-in removed', [
                'student_id' => $studentId,
                'session_id' => $sessionId,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove check-in', [
                'student_id' => $studentId,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Clear all check-ins for a session
     *
     * @param int $sessionId
     * @return bool
     */
    public function clearSessionCheckIns(int $sessionId): bool
    {
        try {
            $activeKey = $this->getActiveCheckInsKey($sessionId);
            $studentIds = Redis::smembers($activeKey);
            
            foreach ($studentIds as $studentId) {
                $checkInKey = $this->getCheckInKey($sessionId, $studentId);
                Redis::del($checkInKey);
            }
            
            Redis::del($activeKey);
            
            Log::debug('Session check-ins cleared', ['session_id' => $sessionId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear session check-ins', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cache session configuration
     *
     * @param int $sessionId
     * @param array $config
     * @return bool
     */
    public function cacheSessionConfig(int $sessionId, array $config): bool
    {
        try {
            $key = $this->getSessionConfigKey($sessionId);
            Redis::setex($key, self::CACHE_TTL_SESSION_CONFIG, json_encode($config));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cache session config', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get cached session configuration
     *
     * @param int $sessionId
     * @return array|null
     */
    public function getCachedSessionConfig(int $sessionId): ?array
    {
        try {
            $key = $this->getSessionConfigKey($sessionId);
            $data = Redis::get($key);
            return $data ? json_decode($data, true) : null;
        } catch (\Exception $e) {
            Log::error('Failed to get cached session config', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if Redis is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            Log::warning('Redis is not available', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get session cache key
     *
     * @param int $sessionId
     * @return string
     */
    private function getSessionKey(int $sessionId): string
    {
        return self::PREFIX_SESSION . $sessionId;
    }

    /**
     * Get session config cache key
     *
     * @param int $sessionId
     * @return string
     */
    private function getSessionConfigKey(int $sessionId): string
    {
        return self::PREFIX_SESSION . 'config:' . $sessionId;
    }

    /**
     * Get check-in key
     *
     * @param int $sessionId
     * @param int $studentId
     * @return string
     */
    private function getCheckInKey(int $sessionId, int $studentId): string
    {
        return self::PREFIX_CHECKIN . $sessionId . ':' . $studentId;
    }

    /**
     * Get active check-ins set key
     *
     * @param int $sessionId
     * @return string
     */
    private function getActiveCheckInsKey(int $sessionId): string
    {
        return self::PREFIX_ACTIVE . 'checkins:' . $sessionId;
    }
}

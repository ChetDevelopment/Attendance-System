<?php

namespace App\Http\Middleware;

use App\Services\SessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class ValidateSessionTime
{
    protected $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if strict session time validation is enabled
        $strictValidation = config('sessions.validation.strict_session_time', true);
        
        if (!$strictValidation) {
            return $next($request);
        }
        
        // Get current active session
        $currentSession = $this->sessionService->getCurrentSession();
        
        if (!$currentSession) {
            return response()->json([
                'success' => false,
                'message' => 'No active session at current time',
                'error' => 'session_inactive',
                'current_time' => Carbon::now(config('sessions.timezone', 'Asia/Bangkok'))->format('H:i:s'),
            ], 403);
        }
        
        // Validate late threshold
        $now = Carbon::now(config('sessions.timezone', 'Asia/Bangkok'));
        $isLate = $currentSession->isLate($now);
        
        // Add session info to request for later use
        $request->attributes->set('current_session', $currentSession);
        $request->attributes->set('is_late', $isLate);
        $request->attributes->set('check_in_time', $now);
        
        return $next($request);
    }

    /**
     * Validate a specific session by ID
     */
    public function validateSession(Request $request, int $sessionId): array
    {
        $session = $this->sessionService->getSessionById($sessionId);
        
        if (!$session) {
            return [
                'valid' => false,
                'message' => 'Session not found',
            ];
        }
        
        if (!$session->is_active) {
            return [
                'valid' => false,
                'message' => 'Session is not active',
            ];
        }
        
        $now = Carbon::now(config('sessions.timezone', 'Asia/Bangkok'));
        
        if (!$session->isTimeWithinSession($now)) {
            return [
                'valid' => false,
                'message' => 'Current time is outside session hours',
                'session_start' => $session->start_time,
                'session_end' => $session->end_time,
                'current_time' => $now->format('H:i'),
            ];
        }
        
        return [
            'valid' => true,
            'session' => $session,
            'is_late' => $session->isLate($now),
            'minutes_late' => $session->getMinutesLate($now),
        ];
    }
}

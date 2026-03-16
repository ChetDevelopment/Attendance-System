<?php

namespace App\Http\Controllers;

use App\Services\SessionService;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class SessionController extends Controller
{
    protected $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Get all sessions (for admin)
     */
    public function index(): JsonResponse
    {
        $sessions = $this->sessionService->getAllSessions();
        
        return response()->json([
            'success' => true,
            'data' => $sessions,
            'config' => $this->sessionService->getSessionConfig(),
            'message' => 'Sessions retrieved successfully',
        ]);
    }

    /**
     * Get session configuration
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->sessionService->getSessionConfig(),
            'message' => 'Session configuration retrieved successfully',
        ]);
    }

    /**
     * Get active sessions only
     */
    public function active(): JsonResponse
    {
        $sessions = $this->sessionService->getActiveSessions();
        
        return response()->json([
            'success' => true,
            'data' => $sessions,
            'message' => 'Active sessions retrieved successfully',
        ]);
    }

    /**
     * Get current active session
     */
    public function current(): JsonResponse
    {
        $session = $this->sessionService->getCurrentSession();
        
        if (!$session) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'No active session at current time',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $session,
            'message' => 'Current session retrieved successfully',
        ]);
    }

    /**
     * Get next upcoming session
     */
    public function next(): JsonResponse
    {
        $session = $this->sessionService->getNextSession();
        
        if (!$session) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'No upcoming session found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $session,
            'message' => 'Next session retrieved successfully',
        ]);
    }

    /**
     * Get a specific session
     */
    public function show($id): JsonResponse
    {
        $session = $this->sessionService->getSessionById($id);
        
        if (!$session) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Session not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $session,
            'message' => 'Session retrieved successfully',
        ]);
    }

    /**
     * Create a new session
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'order' => 'required|integer|min:1|max:8',
            'late_threshold' => 'nullable|integer|min:0|max:60',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
            'date' => 'nullable|date',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);
        
        // Set default values
        $validated['late_threshold'] = $validated['late_threshold'] ?? config('sessions.default_late_threshold', 15);
        $validated['is_active'] = $validated['is_active'] ?? true;
        
        // Check if order already exists
        $existingSession = Session::where('order', $validated['order'])
            ->where(function ($query) use ($validated) {
                $query->where('date', $validated['date'] ?? null)
                    ->orWhereNull('date');
            })
            ->first();
        
        if ($existingSession) {
            return response()->json([
                'success' => false,
                'message' => 'A session with this order already exists',
            ], 422);
        }
        
        $session = $this->sessionService->createSession($validated);
        
        return response()->json([
            'success' => true,
            'data' => $session,
            'message' => 'Session created successfully',
        ], 201);
    }

    /**
     * Update an existing session
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'order' => 'sometimes|integer|min:1|max:8',
            'late_threshold' => 'nullable|integer|min:0|max:60',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
            'date' => 'nullable|date',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);
        
        $session = $this->sessionService->updateSession($id, $validated);
        
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $session,
            'message' => 'Session updated successfully',
        ]);
    }

    /**
     * Delete a session
     */
    public function destroy($id): JsonResponse
    {
        $deleted = $this->sessionService->deleteSession($id);
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Session deleted successfully',
        ]);
    }

    /**
     * Toggle session active status
     */
    public function toggle($id): JsonResponse
    {
        $session = $this->sessionService->toggleSessionStatus($id);
        
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $session,
            'message' => 'Session status toggled successfully',
        ]);
    }

    /**
     * Initialize default PNC sessions
     */
    public function initialize(): JsonResponse
    {
        if ($this->sessionService->areSessionsInitialized()) {
            return response()->json([
                'success' => false,
                'message' => 'Sessions are already initialized',
            ], 400);
        }
        
        $created = $this->sessionService->initializeDefaultSessions();
        
        return response()->json([
            'success' => true,
            'data' => ['created_count' => $created],
            'message' => "Initialized {$created} default PNC sessions",
        ]);
    }

    /**
     * Validate session time
     */
    public function validateTime($id): JsonResponse
    {
        $validation = $this->sessionService->validateSessionTime($id);
        
        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => $validation['reason'],
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'valid' => true,
            'session' => $validation['session'],
            'message' => 'Session time is valid',
        ]);
    }

    /**
     * Check if student can check in
     */
    public function canCheckIn(Request $request, $sessionId): JsonResponse
    {
        $studentId = $request->input('student_id');
        
        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID is required',
            ], 400);
        }
        
        $result = $this->sessionService->canCheckIn($studentId, $sessionId);
        
        return response()->json([
            'success' => true,
            'allowed' => $result['allowed'],
            'message' => $result['reason'],
            'data' => $result,
        ]);
    }

    /**
     * Check late status for a session
     */
    public function checkLate(Request $request, $sessionId): JsonResponse
    {
        $checkInTime = $request->input('check_in_time');
        $time = $checkInTime 
            ? Carbon::parse($checkInTime, config('sessions.timezone', 'Asia/Bangkok'))
            : Carbon::now(config('sessions.timezone', 'Asia/Bangkok'));
        
        $result = $this->sessionService->isLate($sessionId, $time);
        
        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get sessions for a specific date
     */
    public function forDate(Request $request): JsonResponse
    {
        $date = $request->input('date', Carbon::now(config('sessions.timezone', 'Asia/Bangkok'))->toDateString());
        
        $sessions = $this->sessionService->getSessionsForDate($date);
        
        return response()->json([
            'success' => true,
            'data' => $sessions,
            'message' => 'Sessions retrieved successfully',
        ]);
    }
}

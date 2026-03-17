<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Performance Monitoring Middleware
 * 
 * Logs API response times and identifies slow requests
 * Add to routes that need performance monitoring
 */
class PerformanceMonitor
{
    /**
     * Threshold in milliseconds for slow requests (default: 1000ms = 1s)
     */
    private const SLOW_THRESHOLD_MS = 1000;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = uniqid('req_', true);

        // Add request ID to header for tracking
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        // Calculate execution time
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $request->attributes->set('execution_time', $executionTime);

        // Add timing header to response
        $response->headers->set('X-Execution-Time-Ms', round($executionTime, 2));
        $response->headers->set('X-Request-ID', $requestId);

        // Log slow requests
        if ($executionTime > self::SLOW_THRESHOLD_MS) {
            Log::warning('Slow API Request Detected', [
                'request_id' => $requestId,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'execution_time_ms' => round($executionTime, 2),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
            ]);
        }

        return $response;
    }
}

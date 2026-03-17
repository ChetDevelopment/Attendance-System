<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        \Illuminate\Support\Facades\Log::debug('CheckRole: Checking roles ' . implode(',', $roles) . ' for user ' . ($request->user() ? $request->user()->email : 'Guest'));

        if (!Auth::check()) {
            \Illuminate\Support\Facades\Log::warning('CheckRole: Auth::check() failed');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        \Illuminate\Support\Facades\Log::debug('CheckRole: User role is ' . ($user->role ? $user->role->name : 'None'));
        
        // Check if user has any of the required roles
        if ($user->role && in_array($user->role->name, $roles)) {
            return $next($request);
        }

        \Illuminate\Support\Facades\Log::warning('CheckRole: Forbidden for role ' . ($user->role ? $user->role->name : 'None'));
        return response()->json(['message' => 'Forbidden: Insufficient permissions'], 403);
    }
}
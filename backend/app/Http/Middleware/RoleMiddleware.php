<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $role)
    {
        $user = $request->user();

        $hasPivotRole = $user->roles()->where('name', $role)->exists();
        $hasDirectRole = $user->role && (
            strcasecmp($user->role->name, $role) === 0 ||
            strcasecmp($user->role->slug, $role) === 0
        );

        if (!$hasPivotRole && !$hasDirectRole) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}

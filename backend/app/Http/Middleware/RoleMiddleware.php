<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        if (!$user || !$user->relationLoaded('role')) {
            $user?->load('role');
        }

        $userRole = strtolower((string) optional($user->role)->name);
        $allowed = array_map(fn ($role) => strtolower((string) $role), $roles);

        if (!$userRole || !in_array($userRole, $allowed, true)) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

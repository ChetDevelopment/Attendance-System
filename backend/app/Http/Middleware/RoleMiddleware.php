<?php

namespace App\Http\Middleware;

use App\Models\Student;
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

        $userRole = $this->resolveUserRole($user);
        $allowed = array_map(fn ($role) => $this->normalizeRole($role), $roles);

        if (
            !$userRole
            && $user
            && (
                $user->student_id
                || Student::query()->where('email', $user->email)->exists()
            )
        ) {
            $userRole = 'student';
        }

        if (!$userRole || !in_array($userRole, $allowed, true)) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    private function resolveUserRole($user): string
    {
        $role = $user?->role;

        $candidates = [
            $role?->slug,
            $role?->name,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeRole($candidate);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return '';
    }

    private function normalizeRole(?string $role): string
    {
        $normalized = strtolower(trim((string) $role));

        return match ($normalized) {
            'education team', 'education_team', 'education-team', 'education' => 'education',
            'training team', 'training_team', 'training-team', 'training' => 'training',
            default => $normalized,
        };
    }
}

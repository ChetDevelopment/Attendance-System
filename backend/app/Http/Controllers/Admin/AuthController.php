<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\JwtAuthService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const STUDENT_EMAIL_REGEX = '/^[a-z]+\.[a-z]+@student\.passerellesnumeriques\.org$/i';

    public function __construct(
        private JwtAuthService $jwtAuthService
    ) {}

    private function databaseUnavailableResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => 'Database connection is unavailable. Please start the database server and try again.',
            'error' => 'database_unavailable',
        ], 503);
    }

    private function transformUser(User $user): array
    {
        $user->loadMissing('role');
        $payload = $user->toArray();
        $payload['role'] = strtolower((string) optional($user->role)->name);

        return $payload;
    }

    public function register(Request $request)
    {
        if (! $request->isMethod('post')) {
            return response()->json([
                'message' => 'Use POST /api/auth/register with JSON body: name, email, password, password_confirmation.',
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'regex:' . self::STUDENT_EMAIL_REGEX,
                'unique:users,email',
            ],
            'role' => ['nullable', 'string', 'in:admin,teacher'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.regex' => 'Email must use format firstname.lastname@student.passerellesnumeriques.org',
        ]);

        try {
            $roleName = strtolower($validated['role'] ?? 'admin');
            $role = Role::firstOrCreate(['name' => $roleName], ['description' => ucfirst($roleName) . ' role']);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role_id' => $role->id,
                'password' => Hash::make($validated['password']),
            ]);
        } catch (QueryException) {
            return $this->databaseUnavailableResponse();
        }

        // Generate JWT token
        $tokenData = $this->jwtAuthService->getUserWithToken($user);

        return response()->json([
            'message' => 'User registered successfully.',
            'token' => $tokenData['token'],
            'token_type' => $tokenData['token_type'],
            'expires_in' => $tokenData['expires_in'],
            'user' => $this->transformUser($user),
        ], 201);
    }

    public function login(Request $request)
    {
        if (! $request->isMethod('post')) {
            return response()->json([
                'message' => 'Use POST /api/auth/login with JSON body: email, password.',
            ]);
        }

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
            ],
            'password' => ['required', 'string'],
        ]);

        try {
            $user = User::where('email', $validated['email'])->first();
        } catch (QueryException) {
            return $this->databaseUnavailableResponse();
        }

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Generate JWT token using JwtAuthService
        $tokenData = $this->jwtAuthService->getUserWithToken($user);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $tokenData['token'],
            'token_type' => $tokenData['token_type'],
            'expires_in' => $tokenData['expires_in'],
            'user' => $this->transformUser($user),
        ]);
    }

    public function logout(Request $request)
    {
        // Invalidate the JWT token
        $this->jwtAuthService->invalidateToken();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    public function me(Request $request)
    {
        // Get user from JWT token
        $user = $this->jwtAuthService->getUserFromToken();
        
        if (! $user) {
            return response()->json([
                'message' => 'Invalid or expired token',
                'user' => null,
            ], 401);
        }

        return response()->json([
            'user' => $this->transformUser($user),
        ]);
    }

    public function refresh(Request $request)
    {
        if (! $request->isMethod('post')) {
            return response()->json([
                'message' => 'Use POST /api/auth/refresh to refresh the token.',
            ]);
        }

        $token = $this->jwtAuthService->refreshToken();

        if (!$token) {
            return response()->json([
                'message' => 'Unable to refresh token. Please login again.',
            ], 401);
        }

        $user = $this->jwtAuthService->getUserFromToken();

        return response()->json([
            'message' => 'Token refreshed successfully.',
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->jwtAuthService->getTokenTTL() * 60,
            'user' => $user ? $this->transformUser($user) : null,
        ]);
    }
}

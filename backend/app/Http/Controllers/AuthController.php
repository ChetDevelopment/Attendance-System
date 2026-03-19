<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService)
    {
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->activityLogService->recordFromRequest(
            $user,
            $request,
            'User registered',
            'Registered new account for ' . $user->email
        );

        return response()->json([
            'message' => 'User registered successfully.',
            'token' => $token,
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->activityLogService->recordFromRequest(
            $user,
            $request,
            'User login',
            'User logged in successfully'
        );

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $this->transformUser($user),
        ]);
    }

    public function logout(Request $request)
    {
        $this->activityLogService->recordFromRequest(
            $request->user(),
            $request,
            'User logout',
            'User logged out'
        );

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->transformUser($request->user()),
        ]);
    }
}

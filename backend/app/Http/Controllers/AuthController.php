<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    private function getStudentRole(): Role
    {
        return Role::query()
            ->whereRaw('LOWER(name) = ?', ['student'])
            ->first()
            ?? Role::create(['name' => 'Student']);
    }

    private function findStudentByEmail(string $email): ?Student
    {
        return Student::query()
            ->where('email', $email)
            ->first();
    }

    private function syncUserWithStudent(User $user, Student $student): User
    {
        $studentRole = $this->getStudentRole();

        // Don't link teachers/admins to students at all - only sync for actual students
        $currentRole = $user->role;
        $isTeacherOrAdmin = $currentRole &&
            in_array(strtolower($currentRole->name ?? ''), ['teacher', 'admin', 'education team', 'training team']);

        // If user is a teacher/admin, don't link to student or sync any data
        // Also clear any incorrect student_id they might have from previous logins
        if ($isTeacherOrAdmin) {
            if ($user->student_id) {
                $user->student_id = null;
                $user->save();
            }
            return $user;
        }

        $updates = [];

        if ((int) $user->student_id !== (int) $student->id) {
            $updates['student_id'] = $student->id;
        }

        if ((int) $user->role_id !== (int) $studentRole->id) {
            $updates['role_id'] = $studentRole->id;
        }

        // Only update name from student for users with student role
        if (empty($user->name) && !empty($student->fullname)) {
            $updates['name'] = $student->fullname;
        }

        if (!empty($updates)) {
            $user->fill($updates);
            $user->save();
            $user->refresh();
        }

        return $user;
    }

    private function transformUser(User $user): array
    {
        $user->loadMissing('role');
        $payload = $user->toArray();
        $resolvedRole = strtolower((string) optional($user->role)->name);

        if (!$resolvedRole && ($user->student_id || $this->findStudentByEmail((string) $user->email))) {
            $resolvedRole = 'student';
        }

        $payload['role'] = $resolvedRole;

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

        $roleName = Role::find($validated['role_id'])?->name;
        if ($roleName && strtolower($roleName) === 'student') {
            $student = Student::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'fullname' => $validated['name'],
                    'is_active' => true,
                ]
            );

            $user = $this->syncUserWithStudent($user, $student);
        }

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
        $student = $this->findStudentByEmail($validated['email']);

        // Load role early so syncUserWithStudent can make informed decisions
        if ($user) {
            $user->load('role');
        }

        if ($user && Hash::check($validated['password'], $user->password)) {
            if ($student) {
                $user = $this->syncUserWithStudent($user, $student);
            }
        } elseif ($student && !empty($student->password) && Hash::check($validated['password'], $student->password)) {
            $user = $user ?: new User();
            $user->fill([
                'name' => $user->name ?: ($student->fullname ?: trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))),
                'email' => $student->email,
                'password' => $student->password,
            ]);
            $user->save();
            $user->load('role'); // Ensure role is loaded before sync
            $user = $this->syncUserWithStudent($user, $student);
        } else {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->load('role');
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    public function index()
    {
        $cacheKey = 'admin_users_list';
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () {
            // Only return non-student users (admin, teacher roles)
            // Using join is faster than whereHas for this simple check
            return User::select('users.*')
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->whereIn('roles.name', ['admin', 'teacher', 'education'])
                ->with('role')
                ->get();
        });
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        
        // Check if this is a student role
        $role = Role::find($validated['role_id']);
        $isStudentRole = $role && strtolower(trim((string)$role->name)) === 'student';
        
        $studentId = $validated['student_id'] ?? null;
        
        // If this is a student role and no student_id provided, create a Student record
        if ($isStudentRole && !$studentId) {
            // Extract name parts from email (e.g., firstname.lastname@... -> firstname lastname)
            $email = $validated['email'];
            $emailParts = explode('@', $email);
            $usernameFromEmail = $emailParts[0] ?? 'student_' . time();
            $nameParts = explode('.', $usernameFromEmail);
            $fullname = $validated['name'] ?? (ucfirst($nameParts[0] ?? 'Student') . ' ' . ucfirst($nameParts[1] ?? 'User'));
            
            // Create a new Student record
            $student = Student::create([
                'fullname' => $fullname,
                'username' => $usernameFromEmail,
                'email' => $email,
                'password' => $validated['password'] ?? 'password123',
                'generation' => date('Y'),
                'class' => 'Default Class',
                'gender' => 'Male', // Default value
                'parent_number' => 'N/A',
                'contact' => 'N/A',
            ]);
            
            $studentId = $student->id;
            
            // Bump cache version since we added a student
            Cache::forever('students:index:version', (int) Cache::get('students:index:version', 1) + 1);
        }
        
        // If student_id is still null but we have a student record with matching email, find it
        if ($isStudentRole && !$studentId) {
            $existingStudent = Student::where('email', $validated['email'])->first();
            if ($existingStudent) {
                $studentId = $existingStudent->id;
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role_id' => $request->role_id,
            'student_id' => $studentId,
        ]);

        Cache::forget('admin_users_list');

        return response()->json($user->load('role'), 201);
    }

    public function show(User $user)
    {
        return $user->load('role');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();
        // Password is automatically hashed by User model's 'hashed' cast
        $user->update($validated);
        Cache::forget('admin_users_list');
        return response()->json($user->load('role'));
    }

    public function resetPassword(User $user)
    {
        $user->update(['password' => 'password123']);
        return response()->json(['message' => 'Password reset to default (password123) successfully.']);
    }

    public function destroy(User $user)
    {
        $user->delete();
        Cache::forget('admin_users_list');
        return response()->json(['message' => 'User deleted']);
    }
}

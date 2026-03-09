<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::with(['role', 'roles'])->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->roles()->syncWithoutDetaching([$request->role_id]);

        return response()->json($user->load(['role', 'roles']), 201);
    }

    public function show($id)
    {
        return response()->json(User::with('roles')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($id),
            ],
            'password' => 'sometimes|min:6',
            'role_id' => 'sometimes|exists:roles,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $request->only(['name', 'email', 'role_id', 'is_active']);

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($request->filled('role_id')) {
            $user->roles()->syncWithoutDetaching([$request->role_id]);
        }

        return response()->json($user->load(['role', 'roles']));
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return response()->json(['message' => 'User deleted']);
    }

    public function assignRole(Request $request, $id)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id'
        ]);

        $user = User::findOrFail($id);
        $user->role_id = $request->role_id;
        $user->save();
        $user->roles()->sync([$request->role_id]);

        return response()->json(['message' => 'Role assigned successfully']);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index()
    {
        return response()->json(SchoolClass::with('students')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'teacher_id' => 'nullable|exists:users,id'
        ]);

        $class = SchoolClass::create($request->all());

        return response()->json($class, 201);
    }

    public function show($id)
    {
        return response()->json(SchoolClass::with('students')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $class = SchoolClass::findOrFail($id);
        $class->update($request->all());

        return response()->json($class);
    }

    public function destroy($id)
    {
        $class = SchoolClass::with('students')->findOrFail($id);

        if ($class->students()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete class with students'
            ], 400);
        }

        $class->delete();

        return response()->json(['message' => 'Class deleted']);
    }
}
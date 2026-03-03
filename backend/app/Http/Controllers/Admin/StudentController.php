<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index()
    {
        return response()->json(Student::with('class')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|unique:students,student_id',
            'name' => 'required|string',
            'class_id' => 'nullable|exists:classes,id'
        ]);

        $student = Student::create($request->all());

        return response()->json($student, 201);
    }

    public function show($id)
    {
        return response()->json(Student::with('class')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'student_id' => [
                'sometimes',
                Rule::unique('students')->ignore($id)
            ],
            'class_id' => 'nullable|exists:classes,id'
        ]);

        $student->update($request->all());

        return response()->json($student);
    }

    public function destroy($id)
    {
        Student::findOrFail($id)->delete();

        return response()->json(['message' => 'Student deleted']);
    }
}
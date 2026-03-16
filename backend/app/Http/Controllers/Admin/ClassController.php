<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassRequest;
use App\Http\Requests\Admin\UpdateClassRequest;
use App\Models\StudentClass;

class ClassController extends Controller
{
    public function index()
    {
        return StudentClass::query()
            ->with('academicYear:id,name')
            ->withCount('students')
            ->orderBy('class_name')
            ->get();
    }

    public function store(StoreClassRequest $request)
    {
        $class = StudentClass::create($request->validated());
        return response()->json($class, 201);
    }

    public function show(StudentClass $class)
    {
        return $class;
    }

    public function update(UpdateClassRequest $request, StudentClass $class)
    {
        $class->update($request->validated());
        return response()->json($class);
    }

    public function destroy(StudentClass $class)
    {
        $class->delete();
        return response()->json(['message' => 'Class deleted']);
    }
}

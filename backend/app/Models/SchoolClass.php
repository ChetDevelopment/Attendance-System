<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;

class ClassController extends Controller
{
    public function destroy($id)
    {
        $class = SchoolClass::with('students')->findOrFail($id);

        if ($class->students()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete class with students'
            ], 400);
        }

        $class->delete();

        return response()->json([
            'message' => 'Class deleted'
        ]);
    }
}
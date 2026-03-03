<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index()
    {
        return response()->json(AcademicYear::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|string'
        ]);

        $year = AcademicYear::create([
            'year' => $request->year,
            'is_active' => false
        ]);

        return response()->json($year, 201);
    }

    public function activate($id)
    {
        AcademicYear::query()->update(['is_active' => false]);

        $year = AcademicYear::findOrFail($id);
        $year->is_active = true;
        $year->save();

        return response()->json(['message' => 'Academic year activated']);
    }

    public function destroy($id)
    {
        AcademicYear::findOrFail($id)->delete();

        return response()->json(['message' => 'Academic year deleted']);
    }
}
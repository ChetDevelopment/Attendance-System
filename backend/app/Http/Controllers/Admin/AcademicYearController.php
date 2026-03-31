<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicYearRequest;
use App\Http\Requests\Admin\UpdateAcademicYearRequest;
use App\Models\AcademicYear;

class AcademicYearController extends Controller
{
    public function index()
    {
        return AcademicYear::query()
            ->withCount('classes')
            ->orderByDesc('id')
            ->get();
    }

    public function store(StoreAcademicYearRequest $request)
    {
        $year = AcademicYear::create($request->validated());

        // Keep only one "Current" / active academic year at a time.
        if ($year->status === 'Current') {
            $year->update(['is_active' => true]);

            AcademicYear::query()
                ->whereKeyNot($year->id)
                ->where('status', 'Current')
                ->update(['status' => 'Close', 'is_active' => false]);
        } else {
            $year->update(['is_active' => false]);
        }
        return response()->json($year, 201);
    }

    public function show(AcademicYear $academicYear)
    {
        return $academicYear;
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear)
    {
        $academicYear->update($request->validated());

        // Keep only one "Current" / active academic year at a time.
        if ($academicYear->status === 'Current') {
            $academicYear->update(['is_active' => true]);

            AcademicYear::query()
                ->whereKeyNot($academicYear->id)
                ->where('status', 'Current')
                ->update(['status' => 'Close', 'is_active' => false]);
        } else {
            $academicYear->update(['is_active' => false]);
        }
        return response()->json($academicYear);
    }

    public function destroy(AcademicYear $academicYear)
    {
        $academicYear->delete();
        return response()->json(['message' => 'Academic year deleted']);
    }
}

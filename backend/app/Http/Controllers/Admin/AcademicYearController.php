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
        return response()->json($year, 201);
    }

    public function show(AcademicYear $academicYear)
    {
        return $academicYear;
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear)
    {
        $academicYear->update($request->validated());
        return response()->json($academicYear);
    }

    public function destroy(AcademicYear $academicYear)
    {
        $academicYear->delete();
        return response()->json(['message' => 'Academic year deleted']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $attendances = Attendance::where('user_id', auth()->id())
            ->latest('date')
            ->get();

        return response()->json($attendances);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,late'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $attendance = Attendance::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return response()->json($attendance, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance)
    {
        abort_if($attendance->user_id !== auth()->id(), 403, 'Unauthorized.');

        return response()->json($attendance);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        abort_if($attendance->user_id !== auth()->id(), 403, 'Unauthorized.');

        $validated = $request->validate([
            'date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:present,absent,late'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $attendance->update($validated);

        return response()->json($attendance);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendance)
    {
        abort_if($attendance->user_id !== auth()->id(), 403, 'Unauthorized.');

        $attendance->delete();

        return response()->json([
            'message' => 'Attendance deleted successfully.',
        ]);
    }
}

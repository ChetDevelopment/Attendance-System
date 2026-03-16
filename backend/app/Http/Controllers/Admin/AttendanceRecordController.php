<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('attendance_records as ar')
            ->join('students as s', 's.id', '=', 'ar.student_id')
            ->join('sessions as ses', 'ses.id', '=', 'ar.session_id')
            ->leftJoin('users as u', 'u.id', '=', 'ar.submitted_by')
            ->select([
                'ar.id',
                'ar.student_id',
                'ar.session_id',
                'ar.status',
                'ar.location',
                'ar.is_locked',
                'ar.created_at',
                's.id as student_ref_id',
                's.fullname as student_name',
                's.username as student_code',
                's.class as class_name',
                'ses.name as session_name',
                'u.name as submitted_by_name',
            ])
            ->orderByDesc('ar.created_at');

        if ($request->filled('status')) {
            $query->where('ar.status', $request->string('status'));
        }

        if ($request->filled('date')) {
            $date = Carbon::parse((string) $request->string('date'));
            $query->whereBetween('ar.created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
        }

        if ($request->filled('q')) {
            $q = '%' . strtolower((string) $request->string('q')) . '%';
            $query->where(function ($inner) use ($q) {
                $inner->whereRaw('LOWER(s.fullname) LIKE ?', [$q])
                    ->orWhereRaw('LOWER(s.username) LIKE ?', [$q])
                    ->orWhereRaw('LOWER(s.class) LIKE ?', [$q])
                    ->orWhereRaw('LOWER(ses.name) LIKE ?', [$q]);
            });
        }

        $records = $query->paginate(25);
        $records->getCollection()->transform(fn ($row) => $this->mapRecord($row));

        return response()->json($records);
    }

    public function update(Request $request, AttendanceRecord $attendanceRecord)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Present,Absent,Late,Excused'],
        ]);

        if ($attendanceRecord->is_locked) {
            return response()->json([
                'message' => 'Attendance is locked and cannot be modified.',
            ], 423);
        }

        $attendanceRecord->update([
            'status' => $validated['status'],
            'submitted_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Attendance record updated successfully.',
        ]);
    }

    public function unlock(AttendanceRecord $attendanceRecord)
    {
        $attendanceRecord->update(['is_locked' => false]);

        return response()->json([
            'message' => 'Attendance record unlocked successfully.',
        ]);
    }

    public function sessions()
    {
        $sessions = Session::query()
            ->select('id', 'name', 'start_time', 'end_time', 'order')
            ->orderBy('order')
            ->get();

        return response()->json($sessions);
    }

    /**
     * Manual correction for attendance records (admin override).
     * This bypasses the locked check and session time validation.
     */
    public function manualCorrection(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'session_id' => 'required|exists:sessions,id',
            'status' => 'required|in:Present,Absent,Late,Excused',
        ]);

        $attendance = AttendanceRecord::updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'session_id' => $validated['session_id'],
            ],
            [
                'status' => $validated['status'],
                'submitted_by' => $request->user()->id,
                'is_locked' => false, // Unlock when manually corrected
            ]
        );

        $record = DB::table('attendance_records as ar')
            ->join('students as s', 's.id', '=', 'ar.student_id')
            ->join('sessions as ses', 'ses.id', '=', 'ar.session_id')
            ->leftJoin('users as u', 'u.id', '=', 'ar.submitted_by')
            ->select([
                'ar.id',
                'ar.student_id',
                'ar.session_id',
                'ar.status',
                'ar.location',
                'ar.is_locked',
                'ar.created_at',
                's.id as student_ref_id',
                's.fullname as student_name',
                's.username as student_code',
                's.class as class_name',
                'ses.name as session_name',
                'u.name as submitted_by_name',
            ])
            ->where('ar.id', $attendance->id)
            ->first();

        return response()->json([
            'message' => 'Attendance corrected successfully.',
            'record' => $record ? $this->mapRecord($record) : null,
        ]);
    }

    private function mapRecord(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'student_id' => (int) $row->student_id,
            'session_id' => (int) $row->session_id,
            'status' => (string) $row->status,
            'location' => $row->location ?? null,
            'is_locked' => (bool) ($row->is_locked ?? false),
            'created_at' => $row->created_at ?? null,
            'student' => [
                'id' => (int) ($row->student_ref_id ?? $row->student_id),
                'name' => (string) ($row->student_name ?? 'Unknown'),
                'code' => (string) ($row->student_code ?? ''),
                'class_name' => (string) ($row->class_name ?? ''),
            ],
            'session' => [
                'name' => (string) ($row->session_name ?? ''),
            ],
            'submitted_by' => [
                'name' => (string) ($row->submitted_by_name ?? 'Unknown'),
            ],
        ];
    }
}

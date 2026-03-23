<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\Session;

class TestAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $student = Student::where('student_code', 'TEST-001')->first();
        if (!$student) return;

        $session = Session::where('is_active', true)->first();
        if (!$session) {
            // Create test session if none
            $sessionId = DB::table('sessions')->insertGetId([
                'name' => 'Test Morning Session',
                'order' => 1,
                'start_time' => '08:00:00',
                'end_time' => '09:30:00',
                'is_active' => true,
                'late_after_minutes' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $session = (object) ['id' => $sessionId];
        }

        $teacherId = DB::table('users')->where('role_id', 2)->value('id') ?? 1;
        $dateColumn = Schema::hasColumn('attendance_records', 'attendance_date')
            ? 'attendance_date'
            : 'date';

        $records = [
            [
                'student_id' => $student->id,
                'session_id' => $session->id,
                'status' => 'present',
                $dateColumn => now()->subDays(1)->format('Y-m-d'),
            ],
            [
                'student_id' => $student->id,
                'session_id' => $session->id,
                'status' => 'late',
                $dateColumn => now()->subDays(2)->format('Y-m-d'),
            ],
            [
                'student_id' => $student->id,
                'session_id' => $session->id,
                'status' => 'present',
                $dateColumn => now()->format('Y-m-d'),
            ],
        ];

        foreach ($records as $record) {
            $payload = array_merge($record, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (Schema::hasColumn('attendance_records', 'submitted_by')) {
                $payload['submitted_by'] = $teacherId;
            }

            if (Schema::hasColumn('attendance_records', 'recorded_by')) {
                $payload['recorded_by'] = $teacherId;
            }

            DB::table('attendance_records')->updateOrInsert(
                [
                    'student_id' => $record['student_id'],
                    'session_id' => $record['session_id'],
                    $dateColumn => $record[$dateColumn],
                ],
                $payload
            );
        }

        $this->command->info('Test attendance data created for TEST-001');
    }
}

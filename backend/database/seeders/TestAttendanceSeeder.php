<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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

        // Sample attendance records - removed check_in_time
        DB::table('attendance_records')->insert([
            [
                'student_id' => $student->id,
                'session_id' => $session->id,
                'status' => 'PRESENT',
                'attendance_date' => now()->subDays(1)->format('Y-m-d'),
                'submitted_by' => 6, // test user id
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => $student->id,
                'session_id' => $session->id,
                'status' => 'LATE',
                'attendance_date' => now()->subDays(2)->format('Y-m-d'),
                'submitted_by' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => $student->id,
                'session_id' => $session->id,
                'status' => 'PRESENT',
                'attendance_date' => now()->format('Y-m-d'),
                'submitted_by' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info('Test attendance data created for TEST-001');
    }
}


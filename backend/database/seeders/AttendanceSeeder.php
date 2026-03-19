<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first teacher (Davy - user id 2)
        $teacherId = 2;
        
        // Get the first class
        $classId = 1;
        
        // Get sessions
        $session1 = 1;
        $session2 = 2;
        $session3 = 3;
        $session4 = 4;
        
        // Today's date (2026-03-18)
        $today = '2026-03-18';
        
        // Sample attendance records for today
         DB::table('attendances')->insert([
            [
                'class_id' => $classId,
                'session_id' => $session1,
                'date' => $today,
                'submitted_by' => $teacherId,
                'is_locked' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'class_id' => $classId,
                'session_id' => $session2,
                'date' => $today,
                'submitted_by' => $teacherId,
                'is_locked' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'class_id' => $classId,
                'session_id' => $session3,
                'date' => $today,
                'submitted_by' => $teacherId,
                'is_locked' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'class_id' => $classId,
                'session_id' => $session4,
                'date' => $today,
                'submitted_by' => $teacherId,
                'is_locked' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            // Previous day data
            [
                'class_id' => $classId,
                'session_id' => $session1,
                'date' => '2026-03-04',
                'submitted_by' => $teacherId,
                'is_locked' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'class_id' => $classId,
                'session_id' => $session2,
                'date' => '2026-03-04',
                'submitted_by' => $teacherId,
                'is_locked' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            
        ]);
    }
}

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
         DB::table('attendances')->insert([
            [
                'class_id' => 1,
                'session_id' => 1,
                'date' => '2026-03-04',
                'submitted_by' => 2, // teacher user id
                'is_locked' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'class_id' => 1,
                'session_id' => 2,
                'date' => '2026-03-04',
                'submitted_by' => 2,
                'is_locked' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            
        ]);
    }
}

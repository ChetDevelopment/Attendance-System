<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily to allow truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Delete all sessions to avoid duplicates
        DB::table('sessions')->truncate();

        DB::table('sessions')->insert([
            [
                'name' => 'Session 1',
                'start_time' => '07:30:00',
                'end_time' => '09:00:00',
                'late_after_minutes' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Session 2',
                'start_time' => '10:00:00',
                'end_time' => '11:30:00',
                'late_after_minutes' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Session 3',
                'start_time' => '13:00:00',
                'end_time' => '14:30:00',
                'late_after_minutes' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Session 4',
                'start_time' => '15:30:00',
                'end_time' => '17:00:00',
                'late_after_minutes' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

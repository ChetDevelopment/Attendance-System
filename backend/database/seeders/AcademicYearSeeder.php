<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; 
use Carbon\Carbon;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('academic_years')->insert([
            [
                'name' => '2025-2026',
                'start_date' => '2025-01-06',
                'end_date' => '2026-12-30',
                'is_active' => true, // Only this year is active
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => '2026-2027',
                'start_date' => '2026-01-06',
                'end_date' => '2027-12-30',
                'is_active' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}

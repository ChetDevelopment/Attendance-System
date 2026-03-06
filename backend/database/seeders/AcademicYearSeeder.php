<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;

class AcademicYearSeeder extends Seeder
{
    public function run()
    {
        $years = [
            [
                'name' => '2025-2026',
                'start_date' => '2025-01-06',
                'end_date' => '2026-12-30',
                'is_active' => 1,
            ],
            [
                'name' => '2026-2027',
                'start_date' => '2026-01-06',
                'end_date' => '2027-12-30',
                'is_active' => 0,
            ],
        ];

        foreach ($years as $year) {
            AcademicYear::updateOrCreate(
                ['name' => $year['name']], // check uniqueness
                $year
            );
        }
    }
}
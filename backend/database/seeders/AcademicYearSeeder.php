<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        AcademicYear::query()->update(['is_active' => false]);

        $academicYears = [
            [
                'name' => '2026-2027',
                'start_date' => '2026-09-01',
                'end_date' => '2027-08-31',
                'is_active' => true,
            ],
            [
                'name' => '2025-2026',
                'start_date' => '2025-09-01',
                'end_date' => '2026-08-31',
                'is_active' => false,
            ],
            [
                'name' => '2024-2025',
                'start_date' => '2024-09-01',
                'end_date' => '2025-08-31',
                'is_active' => false,
            ],
        ];

        foreach ($academicYears as $academicYear) {
            AcademicYear::updateOrCreate(
                ['name' => $academicYear['name']],
                $academicYear
            );
        }
    }
}

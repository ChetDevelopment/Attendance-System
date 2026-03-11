<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicYear;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        AcademicYear::firstOrCreate(
            ['id' => 1],
            [
                'name' => '2025-2026',
                'start_date' => '2025-09-01',
                'end_date' => '2026-08-31',
                'is_active' => true,
            ]
        );
    }
}

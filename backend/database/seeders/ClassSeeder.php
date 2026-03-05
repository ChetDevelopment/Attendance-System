<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicYear;
use Carbon\Carbon;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get active academic year
        $academicYear = AcademicYear::where('is_active', true)->first();
         if (!$academicYear) {
            return; // stop if no academic year exists
        }

        DB::table('classes')->insert([
            [
                'name' => 'WEP A',
                'code' => 'WEP-A-2026' . $academicYear->id,
                'academic_year_id' => $academicYear->id,
                'description' => 'Web Programming Class A',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'WEP B',
                'code' => 'WEP-B-2026' . $academicYear->id,
                'academic_year_id' => $academicYear->id,
                'description' => 'Web Programming Class B',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'WEP C',
                'code' => 'WEP-C-2026' . $academicYear->id,
                'academic_year_id' => $academicYear->id,
                'description' => 'Web Programming Class C',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            ]);
    }
}

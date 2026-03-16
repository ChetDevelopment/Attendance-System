<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use Carbon\Carbon;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all academic years
        $academicYears = AcademicYear::all();
        if ($academicYears->isEmpty()) {
            return; // stop if no academic year exists
        }

        // Get teacher users
        $teacher1 = DB::table('users')->where('email', 'teacher@pnc.com')->first();
        $teacher2 = DB::table('users')->where('email', 'radyy@pnc.com')->first();

        foreach ($academicYears as $academicYear) {
            $yearPrefix = str_replace('-', '', $academicYear->name);

            $classes = [
                [
                    'name' => 'WEP A',
                    'code' => 'WEP-A-' . $yearPrefix,
                    'academic_year_id' => $academicYear->id,
                    'description' => 'Web Programming Class A',
                    'teacher_id' => $teacher1->id ?? null,
                    'is_active' => true,
                ],
                [
                    'name' => 'WEP B',
                    'code' => 'WEP-B-' . $yearPrefix,
                    'academic_year_id' => $academicYear->id,
                    'description' => 'Web Programming Class B',
                    'teacher_id' => $teacher2->id ?? null,
                    'is_active' => true,
                ],
                [
                    'name' => 'WEP C',
                    'code' => 'WEP-C-' . $yearPrefix,
                    'academic_year_id' => $academicYear->id,
                    'description' => 'Web Programming Class C',
                    'teacher_id' => $teacher1->id ?? null,
                    'is_active' => true,
                ],
            ];

            foreach ($classes as $class) {
                SchoolClass::updateOrCreate(
                    ['code' => $class['code']],
                    $class
                );
            }
        }
    }
}

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

        // Get teacher users (using actual teacher emails from UserSeeder)
        $teacher1 = DB::table('users')->where('email', 'davy@pnc.com')->first();
        $teacher2 = DB::table('users')->where('email', 'him@pnc.com')->first();
        $teacher3 = DB::table('users')->where('email', 'mengheang@pnc.com')->first();

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
                    'teacher_id' => $teacher3->id ?? null,
                    'is_active' => true,
                ],
            ];

            foreach ($classes as $class) {
                // Update existing class or create new one
                $existingClass = SchoolClass::where('code', $class['code'])->first();
                if ($existingClass) {
                    $existingClass->update(['teacher_id' => $class['teacher_id']]);
                } else {
                    SchoolClass::create($class);
                }
            }
        }
    }
}

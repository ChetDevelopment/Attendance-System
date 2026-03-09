<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Get first class
        $class = SchoolClass::first();

        if (!$class) {
            return; // Stop if no class exists
        }

        DB::table('students')->insert([
            [
                'student_code' => 'PNC2026-037',
                'first_name' => 'Serey',
                'last_name' => 'Phem',
                'email' => 'serey.phem@student.passerellesnumeriques.org',
                'gender' => 'Male',
                'date_of_birth' => '2006-02-12',
                'class_id' => $class->id,
                'qr_code' => 'qr_pnc2026-037.png',
                'face_image' => 'faces/pnc2026037.jpg',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'student_code' => 'PNC2026-038',
                'first_name' => 'Vichet',
                'last_name' => 'Sat',
                'email' => 'vichet.sat@student.passerellesnumeriques.org',
                'gender' => 'Male',
                'date_of_birth' => '2007-05-12',
                'class_id' => $class->id,
                'qr_code' => 'qr_pnc2026-038.png',
                'face_image' => 'faces/pnc2026038.jpg',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}

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

        $students = [
            [
                'student_code' => 'PNC2026-037',
                'first_name' => 'Serey',
                'last_name' => 'Phem',
                'email' => 'serey.phem@student.passerellesnumeriques.org',
                'gender' => 'Male',
                'date_of_birth' => '2006-02-12',
                'class_id' => $class->id,
                'qr_code' => 'qr_pnc2026-037.png',
                'card_id' => 'CARD-037',
                'face_image' => 'faces/pnc2026037.jpg',
                'face_image' => 'pnc2026037.jpg',
                'parent_number' => '+855 12 345 678',
                'contact' => '+855 12 345 678',
                'is_active' => true,
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
                'card_id' => 'CARD-038',
                'face_image' => 'faces/pnc2026038.jpg',
                'parent_number' => '+855 10 234 567',
                'contact' => '+855 10 234 567',
                'is_active' => true,
            ],
            [
                'student_code' => 'PNC2026-039',
                'first_name' => 'Sreyroth',
                'last_name' => 'Sang',
                'email' => 'sreyroth.sang@student.passerellesnumeriques.org',
                'gender' => 'Female',
                'date_of_birth' => '2007-06-11',
                'class_id' => $class->id,
                'qr_code' => 'qr_pnc2026-039.png',
                'face_image' => 'faces/pnc2026039.jpg',
                'parent_number' => '+855 98 765 432',
                'contact' => '+855 98 765 432',
                'is_active' => true,
            ],
        ];

        foreach ($students as $student) {
            Student::firstOrCreate(
                ['student_code' => $student['student_code']],
                $student
            );
        }
    }
}

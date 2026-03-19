<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\StudentClass; 
use Carbon\Carbon;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first class
        $class = StudentClass::first();
        if (!$class) {
            return;
        }

        // Create test students
        $testStudent1 = DB::table('students')->insertGetId([
            'student_code' => 'TEST-001',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'fullname' => 'Test Student',
            'email' => 'test.student@student.passerellesnumeriques.org',
            'gender' => 'Male',
            'date_of_birth' => '2000-01-01',
            'class_id' => $class->id,
            'qr_code' => 'test-qr.png',
            'card_id' => 'TEST-CARD-001',
            'face_image' => 'test-face.jpg',
            'is_active' => true,
            'fingerprint_enrolled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $testStudent2 = DB::table('students')->insertGetId([
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
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Link test student #1 to new User with role 'student'
        $studentRoleId = DB::table('roles')->where('name', 'Student')->value('id');
        if ($studentRoleId) {
            DB::table('users')->insert([
                'name' => 'Test Student',
                'email' => 'test.student@student.passerellesnumeriques.org',
                'password' => '$2y$10$92DgjTkDQWdwgtfoZ49GYO4gVmyq8HHp9SzJA0q93B4qQyL6nH5nW', // password: 'student123'
                'role_id' => $studentRoleId,
                'student_id' => $testStudent1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Original students
        DB::table('students')->insert([
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
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

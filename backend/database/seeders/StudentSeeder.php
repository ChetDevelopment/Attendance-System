<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\StudentClass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $class = StudentClass::first();

        if (!$class) {
            return;
        }

        $students = [
            [
                'student_code' => 'TEST-001',
                'first_name' => 'Test',
                'last_name' => 'Student',
                'fullname' => 'Test Student',
                'username' => 'test.student',
                'email' => 'test.student@student.passerellesnumeriques.org',
                'gender' => 'Male',
                'date_of_birth' => '2000-01-01',
                'class_id' => $class->id,
                'qr_code' => 'test-qr.png',
                'card_id' => 'TEST-CARD-001',
                'face_image' => 'test-face.jpg',
                'is_active' => true,
                'fingerprint_enrolled' => true,
            ],
            [
                'student_code' => 'PNC2026-037',
                'first_name' => 'Serey',
                'last_name' => 'Phem',
                'fullname' => 'Serey Phem',
                'username' => 'serey.phem',
                'email' => 'serey.phem@student.passerellesnumeriques.org',
                'gender' => 'Male',
                'date_of_birth' => '2006-02-12',
                'class_id' => $class->id,
                'qr_code' => 'qr_pnc2026-037.png',
                'card_id' => 'CARD-037',
                'face_image' => 'faces/pnc2026037.jpg',
                'parent_number' => '+855 12 345 678',
                'contact' => '+855 12 345 678',
                'is_active' => true,
            ],
            [
                'student_code' => 'PNC2026-038',
                'first_name' => 'Vichet',
                'last_name' => 'Sat',
                'fullname' => 'Vichet Sat',
                'username' => 'vichet.sat',
                'email' => 'vichet.sat@student.passerellesnumeriques.org',
                'gender' => 'Male',
                'date_of_birth' => '2007-05-12',
                'class_id' => $class->id,
                'qr_code' => 'qr_pnc2026-038.png',
                'card_id' => 'CARD-038',
                'face_image' => 'faces/pnc2026038.jpg',
                'is_active' => true,
            ],
            [
                'student_code' => 'PNC2026-039',
                'first_name' => 'Sreyroth',
                'last_name' => 'Sang',
                'fullname' => 'Sreyroth Sang',
                'username' => 'sreyroth.sang',
                'email' => 'sreyroth.sang@student.passerellesnumeriques.org',
                'gender' => 'Female',
                'date_of_birth' => '2007-06-11',
                'class_id' => $class->id,
                'qr_code' => 'qr_pnc2026-039.png',
                'card_id' => 'CARD-039',
                'face_image' => 'faces/pnc2026039.jpg',
                'parent_number' => '+855 98 765 432',
                'contact' => '+855 98 765 432',
                'is_active' => true,
            ],
            [
                'student_code' => 'PNC2026-051',
                'first_name' => 'Mary',
                'last_name' => 'Sao',
                'fullname' => 'Mary Sao',
                'username' => 'mary.sao',
                'email' => 'mary.sao@student.passerellesnumeriques.org',
                'gender' => 'Female',
                'date_of_birth' => '2006-07-21',
                'class_id' => $class->id,
                'qr_code' => 'qr_pnc2026-051.png',
                'card_id' => 'CARD-051',
                'face_image' => 'faces/pnc2026051.jpg',
                'parent_number' => '+855 98 779 999',
                'contact' => '+855 98 765 431',
                'is_active' => true,
            ],
            [
                'student_code' => 'PNC2026-020',
                'first_name' => 'Vakhim',
                'last_name' => 'Krean',
                'fullname' => 'Vakhim Krean',
                'username' => 'vakhim.krean',
                'email' => 'vakhim.krean@student.passerellesnumeriques.org',
                'gender' => 'Male',
                'date_of_birth' => '2004-05-29',
                'class_id' => $class->id,
                'qr_code' => 'qr_pnc2026-020.png',
                'card_id' => 'CARD-020',
                'face_image' => 'faces/pnc2026020.jpg',
                'parent_number' => '+855 98 997 777',
                'contact' => '+855 98 765 434',
                'is_active' => true,
            ],
        ];

        foreach ($students as $studentData) {
            Student::updateOrCreate(
                ['student_code' => $studentData['student_code']],
                $studentData
            );
        }

        $testStudentId = Student::where('student_code', 'TEST-001')->value('id');
        $studentRoleId = DB::table('roles')
            ->whereRaw('LOWER(name) = ?', ['student'])
            ->value('id');

        if ($testStudentId && $studentRoleId) {
            DB::table('users')->updateOrInsert(
                ['email' => 'test.student@student.passerellesnumeriques.org'],
                [
                    'name' => 'Test Student',
                    'email' => 'test.student@student.passerellesnumeriques.org',
                    'password' => '$2y$10$92DgjTkDQWdwgtfoZ49GYO4gVmyq8HHp9SzJA0q93B4qQyL6nH5nW',
                    'role_id' => $studentRoleId,
                    'student_id' => $testStudentId,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}

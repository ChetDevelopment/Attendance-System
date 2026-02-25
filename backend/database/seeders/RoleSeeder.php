<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrator with full access to the system.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Teacher',
                'slug' => 'teacher',
                'description' => 'Teacher with access to manage classes and students.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Education Team',
                'slug' => 'education_team',
                'description' => 'Education team member with access to view and manage attendance records.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Training Team',
                'slug' => 'training_team',
                'description' => 'Training team member with access to view and manage attendance records.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Student',
                'slug' => 'student',
                'description' => 'Student with access to view attendance records.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

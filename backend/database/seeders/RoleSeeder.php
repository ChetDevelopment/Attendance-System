<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrator with full access to the system.',
            ],
            [
                'name' => 'Teacher',
                'slug' => 'teacher',
                'description' => 'Teacher with access to manage classes and students.',
            ],
            [
                'name' => 'Education Team',
                'slug' => 'education_team',
                'description' => 'Education team member with access to view and manage attendance records.',
            ],
            [
                'name' => 'Training Team',
                'slug' => 'training_team',
                'description' => 'Training team member with access to view and manage attendance records.',
            ],
            [
                'name' => 'Student',
                'slug' => 'student',
                'description' => 'Student with access to view attendance records.',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}

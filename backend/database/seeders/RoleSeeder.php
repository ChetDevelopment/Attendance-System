<?php

namespace Database\Seeders;  // ✅ required

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
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
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
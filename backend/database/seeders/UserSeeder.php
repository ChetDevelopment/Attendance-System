<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@pnc.com',
                'password' => Hash::make('admin123'),
                'role_id' => 1, // Admin role
                'is_active' => true,
            ],
            [
                'name' => 'Teacher User',
                'email' => 'teacher@pnc.com',
                'password' => Hash::make('teacher123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
            ],
            [
                'name' => 'Education Team User',
                'email' => 'education@pnc.com',
                'password' => Hash::make('education123'),
                'role_id' => 3, // Education Team
                'is_active' => true,
            ],
            [
                'name' => 'Training Team User',
                'email' => 'training@pnc.com',
                'password' => Hash::make('training123'),
                'role_id' => 4, // Training Team
                'is_active' => true,
            ],
            [
                'name' => 'Rady Y',
                'email' => 'radyy@pnc.com',
                'password' => Hash::make('rady123'),
                'role_id' => 2, // Teacher
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}

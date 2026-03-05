<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@pnc.com',
                'password' => Hash::make('admin123'), // hashed password
                'role_id' => 1, // Admin role (from RoleSeeder)
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Teacher User',
                'email' => 'teacher@pnc.com',
                'password' => Hash::make('teacher123'),
                'role_id' => 2, // Teacher role
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Education Team User',
                'email' => 'education@pnc.com',
                'password' => Hash::make('education123'),
                'role_id' => 3, // Education Team
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Training Team User',
                'email' => 'training@pnc.com',
                'password' => Hash::make('training123'),
                'role_id' => 4, // Training Team
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

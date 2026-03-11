<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@pnc.com',
                'password' => Hash::make('password'),
                'role_id' => 1,
                'is_active' => 1,
            ],
            [
                'name' => 'Teacher User',
                'email' => 'teacher@pnc.com',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'is_active' => 1,
            ],
            [
                'name' => 'Education Team User',
                'email' => 'education@pnc.com',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'is_active' => 1,
            ],
            [
                'name' => 'Training Team User',
                'email' => 'training@pnc.com',
                'password' => Hash::make('password'),
                'role_id' => 4,
                'is_active' => 1,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']], // check by email
                $user
            );
        }
    }
}